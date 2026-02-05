"""Relation resolution service for foreign key display values."""

from dataclasses import dataclass
from typing import Any

from sqlalchemy import select, text
from sqlalchemy.ext.asyncio import AsyncSession

from autodb.db.models import Rule


@dataclass
class RelationRule:
    """A relation rule for resolving display values."""

    schema_name: str
    table_name: str
    column_name: str
    map_schema: str
    map_table: str
    map_column: str
    map_display: str


class RelationService:
    """Service for resolving foreign key display values via rules."""

    def __init__(self, db: AsyncSession):
        self.db = db

    async def get_relations_for_table(
        self, schema: str, table: str
    ) -> dict[str, RelationRule]:
        """Get all relation rules for a table.

        Returns:
            Dictionary mapping column names to their RelationRule
        """
        query = select(Rule).where(
            Rule.schema_name == schema,
            Rule.table_name == table,
        )
        result = await self.db.execute(query)
        rules = result.scalars().all()

        return {
            rule.column_name: RelationRule(
                schema_name=rule.schema_name,
                table_name=rule.table_name,
                column_name=rule.column_name,
                map_schema=rule.map_schema,
                map_table=rule.map_table,
                map_column=rule.map_column,
                map_display=rule.map_display,
            )
            for rule in rules
        }

    async def get_relation_for_column(
        self, schema: str, table: str, column: str
    ) -> RelationRule | None:
        """Get the relation rule for a specific column."""
        query = select(Rule).where(
            Rule.schema_name == schema,
            Rule.table_name == table,
            Rule.column_name == column,
        )
        result = await self.db.execute(query)
        rule = result.scalar_one_or_none()

        if rule:
            return RelationRule(
                schema_name=rule.schema_name,
                table_name=rule.table_name,
                column_name=rule.column_name,
                map_schema=rule.map_schema,
                map_table=rule.map_table,
                map_column=rule.map_column,
                map_display=rule.map_display,
            )
        return None

    async def fetch_display_values(
        self, rule: RelationRule, ids: list[Any]
    ) -> dict[Any, str]:
        """Fetch display values for a list of IDs using a relation rule.

        Returns:
            Dictionary mapping IDs to their display values
        """
        if not ids:
            return {}

        # Build parameterized query
        placeholders = ", ".join(f":id_{i}" for i in range(len(ids)))
        params = {f"id_{i}": id_val for i, id_val in enumerate(ids)}

        query = text(f"""
            SELECT {rule.map_column}, {rule.map_display}
            FROM {rule.map_schema}.{rule.map_table}
            WHERE {rule.map_column} IN ({placeholders})
        """)

        result = await self.db.execute(query, params)
        return {row[0]: row[1] for row in result.fetchall()}
