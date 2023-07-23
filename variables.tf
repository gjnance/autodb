variable "azurerm_resource_group" {
  type = string
  default = "autodb-dev"
}

variable "vm_admin_username" {
  type = string
  default = "adbuser"
}

variable "mysql_administrator_login" {
    type = string
    default = "autodb_user"
}

variable "mysql_administrator_login_password" {
    type = string
    default = ""
}