"""CRUD operation routes."""

import csv
import io
from typing import Any

from fastapi import APIRouter, Depends, Form, HTTPException, Query, Request
from fastapi.responses import HTMLResponse, StreamingResponse
from sqlalchemy.ext.asyncio import AsyncSession

from autodb.auth import get_current_user
from autodb.auth.azure_ad import User
from autodb.db import get_db
from autodb.db.introspection import get_table_info
from autodb.dependencies import get_templates
from autodb.services import PreferenceService, Role, RoleService, TableService

router = APIRouter(tags=["crud"])


def _check_write_permission(user: User | None) -> None:
    """Check if user has write permission. Raises HTTPException if not."""
    if not user:
        raise HTTPException(status_code=401, detail="Authentication required for this action")

    role = Role(user.role)
    if role not in (Role.USER, Role.ADMIN):
        raise HTTPException(status_code=403, detail="Write permission required")


@router.get("/data/{schema}/{table}", response_class=HTMLResponse)
async def get_table_data(
    request: Request,
    schema: str,
    table: str,
    where: str | None = Query(None),
    order: str | None = Query(None),
    limit: int = Query(100),
    db: AsyncSession = Depends(get_db),
    user: User | None = Depends(get_current_user),
) -> HTMLResponse:
    """Get table data as HTML table.

    Supports filtering with WHERE clause, ordering, and pagination.
    Resolves foreign key display values via autodb_rules.
    """
    templates = get_templates()

    # Only save/load preferences for authenticated users
    if user:
        prefs = PreferenceService(db, user.preferred_username)
        # Get or update cached preferences
        where = await prefs.get_cached_var(schema, table, "where", where, "")
        order = await prefs.get_cached_var(schema, table, "order", order, "")
        limit_str = await prefs.get_cached_var(schema, table, "limit", str(limit), "100")
        limit = int(limit_str) if limit_str else 100
    else:
        # Use provided values or defaults for guests
        where = where or ""
        order = order or ""
        limit = limit if limit else 100

    # Get table info to determine default sort column
    table_info = await get_table_info(db, schema, table)
    first_column = table_info.columns[0].name if table_info.columns else None

    # Default to first column if no order specified
    if not order and first_column:
        order = first_column

    # Execute query
    service = TableService(db)
    try:
        result = await service.select(
            schema=schema,
            table=table,
            where=where if where else None,
            order_by=order if order else None,
            limit=limit if limit > 0 else None,
        )
    except Exception as e:
        return templates.TemplateResponse(
            request,
            "partials/error.html",
            {"error": str(e)},
        )

    # Determine if user can write
    can_write = False
    if user:
        role = Role(user.role)
        can_write = role in (Role.EDITOR, Role.ADMIN)

    return templates.TemplateResponse(
        request,
        "partials/data_table.html",
        {
            "result": result,
            "schema": schema,
            "table": table,
            "where": where,
            "order": order,
            "limit": limit,
            "can_write": can_write,
        },
    )


@router.get("/form/{schema}/{table}", response_class=HTMLResponse)
async def get_insert_form(
    request: Request,
    schema: str,
    table: str,
    db: AsyncSession = Depends(get_db),
    user: User | None = Depends(get_current_user),
) -> HTMLResponse:
    """Get empty form for inserting a new row."""
    _check_write_permission(user)
    templates = get_templates()
    table_info = await get_table_info(db, schema, table)

    from autodb.services import RelationService

    relation_service = RelationService(db)
    relations = await relation_service.get_relations_for_table(schema, table)

    return templates.TemplateResponse(
        request,
        "partials/row_form.html",
        {
            "table_info": table_info,
            "relations": relations,
            "row": None,
            "action": "insert",
        },
    )


@router.get("/form/{schema}/{table}/edit", response_class=HTMLResponse)
async def get_edit_form(
    request: Request,
    schema: str,
    table: str,
    db: AsyncSession = Depends(get_db),
    user: User | None = Depends(get_current_user),
) -> HTMLResponse:
    """Get form for editing an existing row.

    Primary key values are passed as query parameters.
    """
    _check_write_permission(user)
    templates = get_templates()
    table_info = await get_table_info(db, schema, table)

    # Extract primary key values from query params and convert types
    pk_columns = [col.name for col in table_info.primary_key_columns]
    pk_col_map = {col.name: col for col in table_info.primary_key_columns}
    pk_values = {}
    for col_name in pk_columns:
        raw_value = request.query_params.get(col_name)
        if raw_value:
            pk_values[col_name] = _convert_value(raw_value, pk_col_map[col_name].data_type)
        else:
            pk_values[col_name] = None

    if not all(pk_values.values()):
        raise HTTPException(status_code=400, detail="Missing primary key values")

    # Fetch the row
    service = TableService(db)
    row = await service.get_row_by_pk(schema, table, pk_columns, pk_values)

    if not row:
        raise HTTPException(status_code=404, detail="Row not found")

    from autodb.services import RelationService

    relation_service = RelationService(db)
    relations = await relation_service.get_relations_for_table(schema, table)

    return templates.TemplateResponse(
        request,
        "partials/row_form.html",
        {
            "table_info": table_info,
            "relations": relations,
            "row": row,
            "action": "update",
        },
    )


@router.post("/data/{schema}/{table}", response_class=HTMLResponse)
async def insert_row(
    request: Request,
    schema: str,
    table: str,
    db: AsyncSession = Depends(get_db),
    user: User | None = Depends(get_current_user),
) -> HTMLResponse:
    """Insert a new row into the table."""
    _check_write_permission(user)
    templates = get_templates()

    form_data = await request.form()
    table_info = await get_table_info(db, schema, table)

    # Build data dict from form, excluding empty values and auto-increment columns
    data: dict[str, Any] = {}
    for col in table_info.columns:
        if col.is_auto_increment:
            continue
        value = form_data.get(col.name)
        if value is not None and value != "":
            data[col.name] = _convert_value(value, col.data_type)
        elif not col.is_nullable and col.column_default is None:
            # Required field is empty
            return templates.TemplateResponse(
                request,
                "partials/error.html",
                {"error": f"Required field '{col.name}' is empty"},
            )

    service = TableService(db)
    try:
        await service.insert(schema, table, data)
    except Exception as e:
        return templates.TemplateResponse(
            request,
            "partials/error.html",
            {"error": str(e)},
        )

    # Return success message and refresh data
    return templates.TemplateResponse(
        request,
        "partials/success.html",
        {"message": "Row inserted successfully", "schema": schema, "table": table},
    )


@router.put("/data/{schema}/{table}", response_class=HTMLResponse)
async def update_row(
    request: Request,
    schema: str,
    table: str,
    db: AsyncSession = Depends(get_db),
    user: User | None = Depends(get_current_user),
) -> HTMLResponse:
    """Update an existing row."""
    _check_write_permission(user)
    templates = get_templates()

    form_data = await request.form()
    table_info = await get_table_info(db, schema, table)

    # Extract primary key values and convert types
    pk_columns = [col.name for col in table_info.primary_key_columns]
    pk_col_map = {col.name: col for col in table_info.primary_key_columns}
    pk_values = {}
    for col_name in pk_columns:
        pk_val = form_data.get(f"_pk_{col_name}")
        if pk_val is None:
            return templates.TemplateResponse(
                request,
                "partials/error.html",
                {"error": f"Missing primary key value for '{col_name}'"},
            )
        pk_values[col_name] = _convert_value(pk_val, pk_col_map[col_name].data_type)

    # Build data dict from form
    data: dict[str, Any] = {}
    for col in table_info.columns:
        if col.is_auto_increment or col.name in pk_columns:
            continue
        value = form_data.get(col.name)
        if value is not None:
            data[col.name] = _convert_value(value, col.data_type) if value != "" else None

    if not data:
        return templates.TemplateResponse(
            request,
            "partials/error.html",
            {"error": "No data to update"},
        )

    service = TableService(db)
    try:
        await service.update(schema, table, data, pk_columns, pk_values)
    except Exception as e:
        return templates.TemplateResponse(
            request,
            "partials/error.html",
            {"error": str(e)},
        )

    return templates.TemplateResponse(
        request,
        "partials/success.html",
        {"message": "Row updated successfully", "schema": schema, "table": table},
    )


@router.delete("/data/{schema}/{table}", response_class=HTMLResponse)
async def delete_row(
    request: Request,
    schema: str,
    table: str,
    db: AsyncSession = Depends(get_db),
    user: User | None = Depends(get_current_user),
) -> HTMLResponse:
    """Delete a row from the table.

    Primary key values are passed as form data with _pk_ prefix.
    """
    _check_write_permission(user)
    templates = get_templates()

    form_data = await request.form()
    table_info = await get_table_info(db, schema, table)

    # Extract primary key values and convert types
    pk_columns = [col.name for col in table_info.primary_key_columns]
    pk_col_map = {col.name: col for col in table_info.primary_key_columns}
    pk_values = {}
    for col_name in pk_columns:
        pk_val = form_data.get(f"_pk_{col_name}")
        if pk_val is None:
            return templates.TemplateResponse(
                request,
                "partials/error.html",
                {"error": f"Missing primary key value for '{col_name}'"},
            )
        pk_values[col_name] = _convert_value(pk_val, pk_col_map[col_name].data_type)

    service = TableService(db)
    try:
        deleted = await service.delete(schema, table, pk_columns, pk_values)
        if not deleted:
            return templates.TemplateResponse(
                request,
                "partials/error.html",
                {"error": "Row not found"},
            )
    except Exception as e:
        return templates.TemplateResponse(
            request,
            "partials/error.html",
            {"error": str(e)},
        )

    return templates.TemplateResponse(
        request,
        "partials/success.html",
        {"message": "Row deleted successfully", "schema": schema, "table": table},
    )


@router.get("/export/{schema}/{table}")
async def export_csv(
    schema: str,
    table: str,
    where: str | None = Query(None),
    order: str | None = Query(None),
    limit: int | None = Query(None),
    db: AsyncSession = Depends(get_db),
) -> StreamingResponse:
    """Export table data as CSV."""
    service = TableService(db)
    result = await service.select(
        schema=schema,
        table=table,
        where=where,
        order_by=order,
        limit=limit,
        include_relations=True,
    )

    # Build CSV
    output = io.StringIO()
    writer = csv.writer(output)

    # Header row
    headers = [col.name for col in result.columns]
    writer.writerow(headers)

    # Data rows
    for row in result.rows:
        csv_row = []
        for col in result.columns:
            value = row.get(col.name)
            # Use display value for relational columns
            if col.name in result.relations and value is not None:
                display = result.get_display_value(col.name, value)
                csv_row.append(display if display else value)
            else:
                csv_row.append(value if value is not None else "")
        writer.writerow(csv_row)

    output.seek(0)
    return StreamingResponse(
        iter([output.getvalue()]),
        media_type="text/csv",
        headers={"Content-Disposition": f"attachment; filename={schema}_{table}.csv"},
    )


def _convert_value(value: Any, data_type: str) -> Any:
    """Convert form value to appropriate Python type based on column data type."""
    if value is None or value == "":
        return None

    if data_type in ("integer", "bigint", "smallint"):
        return int(value)
    elif data_type in ("numeric", "decimal", "real", "double precision"):
        return float(value)
    elif data_type == "boolean":
        return value.lower() in ("true", "1", "yes", "on")
    else:
        return str(value)
