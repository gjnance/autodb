"""Database introspection for dynamic table discovery."""

from dataclasses import dataclass

from sqlalchemy import text
from sqlalchemy.ext.asyncio import AsyncSession


@dataclass
class ColumnInfo:
    """Information about a database column."""

    name: str
    data_type: str
    is_nullable: bool
    column_default: str | None
    is_primary_key: bool
    is_auto_increment: bool
    character_maximum_length: int | None = None

    @property
    def is_required(self) -> bool:
        """Check if column requires a value (NOT NULL and no default)."""
        return not self.is_nullable and self.column_default is None and not self.is_auto_increment


@dataclass
class TableInfo:
    """Information about a database table."""

    schema: str
    name: str
    columns: list[ColumnInfo]

    @property
    def full_name(self) -> str:
        """Get fully qualified table name."""
        return f"{self.schema}.{self.name}"

    @property
    def primary_key_columns(self) -> list[ColumnInfo]:
        """Get primary key columns."""
        return [col for col in self.columns if col.is_primary_key]


async def get_schemas(db: AsyncSession) -> list[str]:
    """Get list of schemas (databases) available to browse.

    Excludes system schemas like pg_catalog and information_schema.
    """
    query = text("""
        SELECT schema_name
        FROM information_schema.schemata
        WHERE schema_name NOT IN ('pg_catalog', 'information_schema', 'pg_toast')
        ORDER BY schema_name
    """)
    result = await db.execute(query)
    return [row[0] for row in result.fetchall()]


async def get_tables(db: AsyncSession, schema: str) -> list[str]:
    """Get list of tables in a schema."""
    query = text("""
        SELECT table_name
        FROM information_schema.tables
        WHERE table_schema = :schema
          AND table_type = 'BASE TABLE'
        ORDER BY table_name
    """)
    result = await db.execute(query, {"schema": schema})
    return [row[0] for row in result.fetchall()]


async def get_table_columns(db: AsyncSession, schema: str, table: str) -> list[ColumnInfo]:
    """Get column information for a table."""
    # Get basic column info
    columns_query = text("""
        SELECT
            c.column_name,
            c.data_type,
            c.is_nullable = 'YES' as is_nullable,
            c.column_default,
            c.character_maximum_length
        FROM information_schema.columns c
        WHERE c.table_schema = :schema
          AND c.table_name = :table
        ORDER BY c.ordinal_position
    """)

    # Get primary key columns
    pk_query = text("""
        SELECT kcu.column_name
        FROM information_schema.table_constraints tc
        JOIN information_schema.key_column_usage kcu
            ON tc.constraint_name = kcu.constraint_name
            AND tc.table_schema = kcu.table_schema
        WHERE tc.constraint_type = 'PRIMARY KEY'
          AND tc.table_schema = :schema
          AND tc.table_name = :table
    """)

    columns_result = await db.execute(columns_query, {"schema": schema, "table": table})
    pk_result = await db.execute(pk_query, {"schema": schema, "table": table})

    pk_columns = {row[0] for row in pk_result.fetchall()}

    columns = []
    for row in columns_result.fetchall():
        col_name, data_type, is_nullable, col_default, char_max_len = row

        # Check if column is auto-increment (serial/identity)
        is_auto = False
        if col_default:
            is_auto = "nextval" in col_default or "generated" in col_default.lower()

        columns.append(
            ColumnInfo(
                name=col_name,
                data_type=data_type,
                is_nullable=is_nullable,
                column_default=col_default,
                is_primary_key=col_name in pk_columns,
                is_auto_increment=is_auto,
                character_maximum_length=char_max_len,
            )
        )

    return columns


async def get_table_info(db: AsyncSession, schema: str, table: str) -> TableInfo:
    """Get complete table information including columns."""
    columns = await get_table_columns(db, schema, table)
    return TableInfo(schema=schema, name=table, columns=columns)


async def table_exists(db: AsyncSession, schema: str, table: str) -> bool:
    """Check if a table exists."""
    query = text("""
        SELECT EXISTS (
            SELECT 1
            FROM information_schema.tables
            WHERE table_schema = :schema
              AND table_name = :table
              AND table_type = 'BASE TABLE'
        )
    """)
    result = await db.execute(query, {"schema": schema, "table": table})
    return result.scalar() or False
