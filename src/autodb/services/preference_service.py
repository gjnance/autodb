"""User preference management service."""

from sqlalchemy import delete, select
from sqlalchemy.ext.asyncio import AsyncSession

from autodb.db.models import UserPreference


class PreferenceService:
    """Service for managing user preferences per table."""

    def __init__(self, db: AsyncSession, username: str = ""):
        self.db = db
        self.username = username

    async def get_cached_var(
        self,
        schema: str,
        table: str,
        var_name: str,
        new_value: str | None = None,
        default: str = "",
    ) -> str:
        """Get or set a cached variable for a table.

        If new_value is provided and different from cached, updates the cache.
        Returns the current value (new_value if provided, else cached, else default).
        """
        if new_value is not None:
            # Check if value changed
            existing = await self._get_pref(schema, table, var_name)
            if existing != new_value:
                await self._set_pref(schema, table, var_name, new_value)
            return new_value

        # Return cached value or default
        cached = await self._get_pref(schema, table, var_name)
        return cached if cached is not None else default

    async def _get_pref(self, schema: str, table: str, var_name: str) -> str | None:
        """Get a preference value from database."""
        query = select(UserPreference.value).where(
            UserPreference.schema_name == schema,
            UserPreference.table_name == table,
            UserPreference.var == var_name,
            UserPreference.username == self.username,
        )
        result = await self.db.execute(query)
        row = result.scalar_one_or_none()
        return row

    async def _set_pref(self, schema: str, table: str, var_name: str, value: str) -> None:
        """Set a preference value in database."""
        # Delete existing
        await self.db.execute(
            delete(UserPreference).where(
                UserPreference.schema_name == schema,
                UserPreference.table_name == table,
                UserPreference.var == var_name,
                UserPreference.username == self.username,
            )
        )

        # Insert new
        pref = UserPreference(
            schema_name=schema,
            table_name=table,
            var=var_name,
            value=value,
            username=self.username,
        )
        self.db.add(pref)
        await self.db.flush()

    async def get_table_prefs(self, schema: str, table: str) -> dict[str, str]:
        """Get all preferences for a table."""
        query = select(UserPreference).where(
            UserPreference.schema_name == schema,
            UserPreference.table_name == table,
            UserPreference.username == self.username,
        )
        result = await self.db.execute(query)
        prefs = result.scalars().all()
        return {p.var: p.value for p in prefs if p.var and p.value}

    async def clear_table_prefs(self, schema: str, table: str) -> None:
        """Clear all preferences for a table."""
        await self.db.execute(
            delete(UserPreference).where(
                UserPreference.schema_name == schema,
                UserPreference.table_name == table,
                UserPreference.username == self.username,
            )
        )
