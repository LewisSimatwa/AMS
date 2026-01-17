// api/loginUser.js

export async function loginUser(email, password) {
  try {
    const body = {
      email: email,
      password: password
    };

    console.log("Login request body:", body);

    const response = await fetch("http://localhost:8000/api/login", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(body),
    });

    console.log("Response status:", response.status);

    const data = await response.json();
    console.log("Response data:", data);

    if (!response.ok) {
      throw new Error(data.error || "Login failed");
    }

    return data;
  } catch (error) {
    console.error("Login error:", error);
    throw new Error(error.message || "Failed to login");
  }
}