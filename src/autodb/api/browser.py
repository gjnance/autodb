"""Database browser routes for schema/table listing."""

from fastapi import APIRouter, Depends, Request
from fastapi.responses import HTMLResponse, JSONResponse
from sqlalchemy.ext.asyncio import AsyncSession

from autodb.auth import get_current_user
from autodb.auth.azure_ad import User
from autodb.db import get_db
from autodb.db.introspection import get_schemas, get_table_columns, get_tables
from autodb.dependencies import get_templates
from autodb.services import PreferenceService, Role, RoleService

router = APIRouter(tags=["browser"])


@router.get("/api/schemas", response_class=HTMLResponse)
async def list_schemas(
    request: Request,
    db: AsyncSession = Depends(get_db),
    user: User | None = Depends(get_current_user),
) -> HTMLResponse:
    """Get list of available database schemas as HTML options."""
    templates = get_templates()
    schemas = await get_schemas(db)

    # Filter schemas based on user role
    role_service = RoleService(db)
    user_role = Role(user.role) if user else Role.GUEST
    schemas = role_service.filter_schemas(user_role, schemas)

    return templates.TemplateResponse(
        request,
        "partials/schema_options.html",
        {"schemas": schemas},
    )


@router.get("/api/tables/{schema}", response_class=HTMLResponse)
async def list_tables(
    request: Request,
    schema: str,
    db: AsyncSession = Depends(get_db),
) -> HTMLResponse:
    """Get list of tables in a schema as HTML options."""
    templates = get_templates()
    tables = await get_tables(db, schema)
    return templates.TemplateResponse(
        request,
        "partials/table_options.html",
        {"tables": tables, "schema": schema},
    )


@router.get("/api/columns/{schema}/{table}", response_class=HTMLResponse)
async def list_columns(
    request: Request,
    schema: str,
    table: str,
    db: AsyncSession = Depends(get_db),
) -> HTMLResponse:
    """Get list of columns for a table as HTML options (for ORDER BY dropdown)."""
    templates = get_templates()
    columns = await get_table_columns(db, schema, table)
    return templates.TemplateResponse(
        request,
        "partials/column_options.html",
        {"columns": columns},
    )


@router.get("/api/preferences/{schema}/{table}")
async def get_preferences(
    schema: str,
    table: str,
    db: AsyncSession = Depends(get_db),
    user: User | None = Depends(get_current_user),
) -> JSONResponse:
    """Get saved preferences for a table.

    Returns empty/default preferences for unauthenticated users.
    """
    # No saved preferences for guests
    if not user:
        return JSONResponse({
            "where": "",
            "order": "",
            "limit": "100",
        })

    prefs = PreferenceService(db, user.preferred_username)
    table_prefs = await prefs.get_table_prefs(schema, table)
    return JSONResponse({
        "where": table_prefs.get("where", ""),
        "order": table_prefs.get("order", ""),
        "limit": table_prefs.get("limit", "100"),
    })
