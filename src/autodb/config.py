"""Configuration settings using Pydantic."""

from functools import lru_cache

from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    """Application settings loaded from environment variables."""

    model_config = SettingsConfigDict(env_file=".env", env_file_encoding="utf-8", extra="ignore")

    # Database
    database_url: str = "postgresql+asyncpg://autodb:autodb@localhost:5432/autodb"

    # AutoDB metadata tables
    autodb_rules_table: str = "autodb.autodb_rules"
    autodb_prefs_table: str = "autodb.autodb_prefs"

    # Session
    secret_key: str = "change-me-in-production"
    session_cookie_name: str = "autodb_session"

    # Azure AD Authentication
    azure_client_id: str = ""
    azure_client_secret: str = ""
    azure_tenant_id: str = ""
    azure_redirect_uri: str = "http://localhost:8000/auth/callback"

    # App settings
    debug: bool = False
    app_title: str = "Postgres Explorer"
    max_data_display_length: int = 100

    @property
    def azure_authority(self) -> str:
        """Azure AD authority URL."""
        return f"https://login.microsoftonline.com/{self.azure_tenant_id}"

    @property
    def azure_scopes(self) -> list[str]:
        """Azure AD scopes for authentication."""
        return ["User.Read"]

    @property
    def auth_enabled(self) -> bool:
        """Check if Azure AD auth is configured."""
        return bool(self.azure_client_id and self.azure_tenant_id)


@lru_cache
def get_settings() -> Settings:
    """Get cached settings instance."""
    return Settings()
