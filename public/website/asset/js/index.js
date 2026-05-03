function togglePassword() {
  const passwordInput = document.getElementById("password");
  const toggleIcon = document.querySelector(".toggle-icon");

  if (passwordInput.type === "password") {
    passwordInput.type = "text";
    toggleIcon.src = "/images/eye-dashed-icon.svg";
  } else {
    passwordInput.type = "password";
    toggleIcon.src = "/images/eye-icon.svg";
  }
}

function toggleConfirmPassword() {
  const confirmPasswordInput = document.getElementById("confirmPassword");
  const toggleIcon = document.querySelector("#confirmPassword ~ .toggle-icon");

  if (confirmPasswordInput.type === "password") {
    confirmPasswordInput.type = "text";
    toggleIcon.src = "/images/eye-dashed-icon.svg";
  } else {
    confirmPasswordInput.type = "password";
    toggleIcon.src = "/images/eye-icon.svg";
  }
}



