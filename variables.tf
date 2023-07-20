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
variable "mysql_administrator_login_password_hash" {
    type = string
    default = ""
}