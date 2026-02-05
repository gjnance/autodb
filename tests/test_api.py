"""Tests for API endpoints."""

import pytest
from httpx import AsyncClient


@pytest.mark.asyncio
async def test_health_check(client: AsyncClient):
    """Test health check endpoint."""
    response = await client.get("/health")
    assert response.status_code == 200
    assert response.json() == {"status": "healthy"}


@pytest.mark.asyncio
async def test_index_page(client: AsyncClient):
    """Test main index page loads."""
    response = await client.get("/")
    assert response.status_code == 200
    assert "AutoDB" in response.text


@pytest.mark.asyncio
async def test_list_schemas(client: AsyncClient):
    """Test schema listing endpoint."""
    response = await client.get("/api/schemas")
    assert response.status_code == 200
    # Should return HTML options
    assert "<option" in response.text or response.text == ""


@pytest.mark.asyncio
async def test_list_tables(client: AsyncClient):
    """Test table listing endpoint."""
    response = await client.get("/api/tables/autodb")
    assert response.status_code == 200


@pytest.mark.asyncio
async def test_get_table_data(client: AsyncClient):
    """Test getting table data."""
    response = await client.get("/data/autodb/contacts")
    assert response.status_code == 200


@pytest.mark.asyncio
async def test_get_table_data_with_limit(client: AsyncClient):
    """Test getting table data with limit."""
    response = await client.get("/data/autodb/contacts?limit=5")
    assert response.status_code == 200


@pytest.mark.asyncio
async def test_get_insert_form(client: AsyncClient):
    """Test getting insert form."""
    response = await client.get("/form/autodb/contacts")
    assert response.status_code == 200
    assert "form" in response.text.lower()


@pytest.mark.asyncio
async def test_suggestions_endpoint(client: AsyncClient):
    """Test suggestions endpoint."""
    response = await client.get("/api/suggest/autodb/contacts/country_id?q=")
    assert response.status_code == 200


@pytest.mark.asyncio
async def test_export_csv(client: AsyncClient):
    """Test CSV export."""
    response = await client.get("/export/autodb/contacts?limit=5")
    assert response.status_code == 200
    assert response.headers["content-type"] == "text/csv; charset=utf-8"


@pytest.mark.asyncio
async def test_auth_status(client: AsyncClient):
    """Test auth status endpoint."""
    response = await client.get("/auth/status")
    assert response.status_code == 200
