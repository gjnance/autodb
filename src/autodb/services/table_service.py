"""Table data service for CRUD operations."""

from dataclasses import dataclass, field
from typing import Any

from sqlalchemy import text
from sqlalchemy.ext.asyncio import AsyncSession

from autodb.db.introspection import ColumnInfo, get_table_columns
from autodb.services.relation_service import RelationRule, RelationService


@dataclass
class QueryResult:
    """Result of a SELECT query with metadata."""

    rows: list[dict[str, Any]]
    columns: list[ColumnInfo]
    relations: dict[str, RelationRule]
    relation_display_values: dict[str, dict[Any, str]] = field(default_factory=dict)
    total_count: int | None = None

    def get_display_value(self, column: str, id_value: Any) -> str | None:
        """Get the display value for a relational column."""
        if column in self.relation_display_values:
            return self.relation_display_values[column].get(id_value)
        return None


class TableService:
    """Service for CRUD operations on database tables."""

    def __init__(self, db: AsyncSession):
        self.db = db
        self.relation_service = RelationService(db)

    async def select(
        self,
        schema: str,
        table: str,
        where: str | None = None,
        order_by: str | None = None,
        limit: int | None = None,
        offset: int = 0,
        include_relations: bool = True,
    ) -> QueryResult:
        """Execute a SELECT query with optional relation resolution.

        Args:
            schema: Database schema name
            table: Table name
            where: Optional WHERE clause (without 'WHERE' keyword)
            order_by: Optional ORDER BY clause (without 'ORDER BY' keyword)
            limit: Maximum number of rows to return
            offset: Number of rows to skip
            include_relations: Whether to resolve foreign key display values

        Returns:
            QueryResult with rows, columns, and relation data
        """
        columns = await get_table_columns(self.db, schema, table)
        relations = {}

        if include_relations:
            relations = await self.relation_service.get_relations_for_table(schema, table)

        # Build the query
        column_names = [col.name for col in columns]
        sql_parts = [f'SELECT {", ".join(column_names)} FROM {schema}.{table}']

        params: dict[str, Any] = {}

        if where:
            # Note: In production, this should use parameterized queries
            # The original PHP intentionally allowed raw SQL for power users
            sql_parts.append(f"WHERE {where}")

        if order_by:
            sql_parts.append(f"ORDER BY {order_by}")

        if limit is not None:
            sql_parts.append("LIMIT :limit")
            params["limit"] = limit

        if offset > 0:
            sql_parts.append("OFFSET :offset")
            params["offset"] = offset

        query = text(" ".join(sql_parts))
        result = await self.db.execute(query, params)

        rows = [dict(zip(column_names, row)) for row in result.fetchall()]

        # Resolve relation display values
        relation_display_values: dict[str, dict[Any, str]] = {}
        if include_relations:
            for col_name, rule in relations.items():
                ids = [row[col_name] for row in rows if row.get(col_name) is not None]
                if ids:
                    display_values = await self.relation_service.fetch_display_values(rule, ids)
                    relation_display_values[col_name] = display_values

        return QueryResult(
            rows=rows,
            columns=columns,
            relations=relations,
            relation_display_values=relation_display_values,
        )

    async def insert(
        self,
        schema: str,
        table: str,
        data: dict[str, Any],
    ) -> dict[str, Any] | None:
        """Insert a new row into the table.

        Args:
            schema: Database schema name
            table: Table name
            data: Dictionary of column names to values

        Returns:
            The inserted row data (if RETURNING is supported)
        """
        if not data:
            raise ValueError("No data provided for insert")

        columns = list(data.keys())
        placeholders = [f":{col}" for col in columns]

        sql = f"""
            INSERT INTO {schema}.{table} ({", ".join(columns)})
            VALUES ({", ".join(placeholders)})
            RETURNING *
        """

        result = await self.db.execute(text(sql), data)
        row = result.fetchone()
        if row:
            return dict(zip(result.keys(), row))
        return None

    async def update(
        self,
        schema: str,
        table: str,
        data: dict[str, Any],
        pk_columns: list[str],
        pk_values: dict[str, Any],
    ) -> dict[str, Any] | None:
        """Update a row in the table.

        Args:
            schema: Database schema name
            table: Table name
            data: Dictionary of column names to new values
            pk_columns: List of primary key column names
            pk_values: Dictionary of primary key column values

        Returns:
            The updated row data
        """
        if not data:
            raise ValueError("No data provided for update")

        if not pk_columns or not pk_values:
            raise ValueError("Primary key columns and values required for update")

        # Build SET clause
        set_parts = [f"{col} = :set_{col}" for col in data.keys()]
        params = {f"set_{col}": val for col, val in data.items()}

        # Build WHERE clause for primary key
        where_parts = [f"{col} = :pk_{col}" for col in pk_columns]
        params.update({f"pk_{col}": pk_values[col] for col in pk_columns})

        sql = f"""
            UPDATE {schema}.{table}
            SET {", ".join(set_parts)}
            WHERE {" AND ".join(where_parts)}
            RETURNING *
        """

        result = await self.db.execute(text(sql), params)
        row = result.fetchone()
        if row:
            return dict(zip(result.keys(), row))
        return None

    async def delete(
        self,
        schema: str,
        table: str,
        pk_columns: list[str],
        pk_values: dict[str, Any],
    ) -> bool:
        """Delete a row from the table.

        Args:
            schema: Database schema name
            table: Table name
            pk_columns: List of primary key column names
            pk_values: Dictionary of primary key column values

        Returns:
            True if a row was deleted
        """
        if not pk_columns or not pk_values:
            raise ValueError("Primary key columns and values required for delete")

        # Build WHERE clause
        where_parts = [f"{col} = :pk_{col}" for col in pk_columns]
        params = {f"pk_{col}": pk_values[col] for col in pk_columns}

        sql = f"""
            DELETE FROM {schema}.{table}
            WHERE {" AND ".join(where_parts)}
        """

        result = await self.db.execute(text(sql), params)
        return result.rowcount > 0

    async def get_row_by_pk(
        self,
        schema: str,
        table: str,
        pk_columns: list[str],
        pk_values: dict[str, Any],
    ) -> dict[str, Any] | None:
        """Get a single row by primary key.

        Args:
            schema: Database schema name
            table: Table name
            pk_columns: List of primary key column names
            pk_values: Dictionary of primary key column values

        Returns:
            Row data as dictionary or None if not found
        """
        where_parts = [f"{col} = :pk_{col}" for col in pk_columns]
        params = {f"pk_{col}": pk_values[col] for col in pk_columns}

        sql = f"""
            SELECT * FROM {schema}.{table}
            WHERE {" AND ".join(where_parts)}
        """

        result = await self.db.execute(text(sql), params)
        row = result.fetchone()
        if row:
            return dict(zip(result.keys(), row))
        return None
