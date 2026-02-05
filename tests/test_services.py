"""Tests for service layer."""

import pytest
from sqlalchemy.ext.asyncio import AsyncSession

from autodb.db.introspection import get_schemas, get_table_columns, get_tables
from autodb.services import PreferenceService, RelationService, TableService


@pytest.mark.asyncio
async def test_get_schemas(db_session: AsyncSession):
    """Test schema discovery."""
    schemas = await get_schemas(db_session)
    assert isinstance(schemas, list)
    assert "autodb" in schemas


@pytest.mark.asyncio
async def test_get_tables(db_session: AsyncSession):
    """Test table discovery in autodb schema."""
    tables = await get_tables(db_session, "autodb")
    assert isinstance(tables, list)
    assert "contacts" in tables
    assert "countries" in tables


@pytest.mark.asyncio
async def test_get_table_columns(db_session: AsyncSession):
    """Test column discovery for contacts table."""
    columns = await get_table_columns(db_session, "autodb", "contacts")
    assert len(columns) > 0

    column_names = [c.name for c in columns]
    assert "id" in column_names
    assert "name" in column_names
    assert "email" in column_names

    # Check primary key detection
    id_col = next(c for c in columns if c.name == "id")
    assert id_col.is_primary_key


@pytest.mark.asyncio
async def test_relation_service_get_relations(db_session: AsyncSession):
    """Test getting relations for a table."""
    service = RelationService(db_session)
    relations = await service.get_relations_for_table("autodb.contacts")

    assert "country_id" in relations
    assert relations["country_id"].related_table == "autodb.countries"
    assert relations["country_id"].display_column == "name"


@pytest.mark.asyncio
async def test_relation_service_fetch_display_values(db_session: AsyncSession):
    """Test fetching display values for foreign keys."""
    service = RelationService(db_session)
    rule = await service.get_relation_for_column("autodb.contacts", "country_id")

    assert rule is not None

    # Fetch display values for country IDs 1 and 2
    display_values = await service.fetch_display_values(rule, [1, 2])

    assert 1 in display_values
    assert display_values[1] == "United States"


@pytest.mark.asyncio
async def test_relation_service_suggestions(db_session: AsyncSession):
    """Test getting suggestions for relational fields."""
    service = RelationService(db_session)
    rule = await service.get_relation_for_column("autodb.contacts", "country_id")

    assert rule is not None

    suggestions = await service.get_suggestions(rule, search_term="United", limit=5)

    assert len(suggestions) > 0
    assert any(s["display"] == "United States" for s in suggestions)


@pytest.mark.asyncio
async def test_table_service_select(db_session: AsyncSession):
    """Test SELECT operation with relations."""
    service = TableService(db_session)
    result = await service.select("autodb", "contacts", limit=5)

    assert len(result.rows) <= 5
    assert len(result.columns) > 0
    assert "country_id" in result.relations

    # Check that display values were fetched
    if result.rows:
        first_row = result.rows[0]
        if first_row.get("country_id"):
            display = result.get_display_value("country_id", first_row["country_id"])
            assert display is not None


@pytest.mark.asyncio
async def test_table_service_select_with_where(db_session: AsyncSession):
    """Test SELECT with WHERE clause."""
    service = TableService(db_session)
    result = await service.select("autodb", "contacts", where="name LIKE '%Wayne%'")

    # Should find Bruce Wayne
    assert any("Wayne" in row.get("name", "") for row in result.rows)


@pytest.mark.asyncio
async def test_preference_service_caching(db_session: AsyncSession):
    """Test preference caching."""
    service = PreferenceService(db_session, user="test_user")

    # Set a preference
    value = await service.get_cached_var("autodb.contacts", "limit", "50", "100")
    assert value == "50"

    # Retrieve cached value
    cached = await service.get_cached_var("autodb.contacts", "limit", None, "100")
    assert cached == "50"

    # Clean up
    await service.clear_table_prefs("autodb.contacts")
