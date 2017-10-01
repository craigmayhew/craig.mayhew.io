resource "null_resource" "build" {
  provisioner "local-exec" {
    command = "cd tools && php build.php pages static"
  }
}