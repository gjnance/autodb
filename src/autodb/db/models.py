"""SQLAlchemy models for Postgres Explorer metadata tables."""

from datetime import datetime

from sqlalchemy import String, DateTime
from sqlalchemy.orm import DeclarativeBase, Mapped, mapped_column


class Base(DeclarativeBase):
    """Base class for all models."""

    pass


class Rule(Base):
    """Relational rules for display columns.

    Maps a source column (containing numeric IDs) to a target table
    where the display value can be looked up.
    """

    __tablename__ = "rules"
    __table_args__ = {"schema": "autodb"}

    id: Mapped[int] = mapped_column(primary_key=True)
    schema_name: Mapped[str] = mapped_column(String(128), nullable=False)
    table_name: Mapped[str] = mapped_column(String(128), nullable=False)
    column_name: Mapped[str] = mapped_column(String(128), nullable=False)
    map_schema: Mapped[str] = mapped_column(String(128), nullable=False)
    map_table: Mapped[str] = mapped_column(String(128), nullable=False)
    map_column: Mapped[str] = mapped_column(String(128), nullable=False)
    map_display: Mapped[str] = mapped_column(String(128), nullable=False)


class UserPreference(Base):
    """User preferences for settings per table.

    Stores cached values like WHERE clauses, sort orders, etc.
    """

    __tablename__ = "user_preferences"
    __table_args__ = {"schema": "autodb"}

    id: Mapped[int] = mapped_column(primary_key=True)
    schema_name: Mapped[str | None] = mapped_column(String(128))
    table_name: Mapped[str | None] = mapped_column(String(128))
    var: Mapped[str | None] = mapped_column(String(64))
    value: Mapped[str | None] = mapped_column(String(64))
    username: Mapped[str] = mapped_column(String(64), default="")


class UserRole(Base):
    """Role-based access control assignments.

    Maps user emails to roles (admin, user, viewer).
    Users not in this table default to 'viewer' role.
    """

    __tablename__ = "user_roles"
    __table_args__ = {"schema": "autodb"}

    id: Mapped[int] = mapped_column(primary_key=True)
    email: Mapped[str] = mapped_column(String(255), unique=True, nullable=False)
    role: Mapped[str] = mapped_column(String(32), nullable=False, default="viewer")
    created_at: Mapped[datetime] = mapped_column(DateTime, default=datetime.utcnow)
