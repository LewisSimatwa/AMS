import axios from "axios";

// Signup function (no token)
export async function createUser({
  institution_id,
  username,
  email,
  password,
  first_name,
  last_name,
}) {
  try {
    const response = await axios.post(
      "http://localhost:8000/api/register.php",
      { institution_id, username, email, password, first_name, last_name }
    );

    // Only return the success message
    return response.data.message;

  } catch (err) {
    if (err.response && err.response.data?.error) {
      throw new Error(err.response.data.error);
    }
    throw new Error("Failed to create account");
  }
}

// Login function (still expects token)
export async function loginUser(email, password) {
  try {
    const response = await axios.post(
      "http://localhost:8000/api/login.php",
      { email, password }
    );

    return response.data; // expects { token, user }
  } catch (err) {
    if (err.response && err.response.data?.error) {
      throw new Error(err.response.data.error);
    }
    throw new Error("Failed to login");
  }
}