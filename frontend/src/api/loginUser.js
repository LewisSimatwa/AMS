// api/loginUser.js - Debug version

export async function loginUser(email, password, institutionId) {
  try {
    const body = {
      email: email,
      password: password,
    };
    
    // Only add institution_id if it's provided (not null for super admin)
    if (institutionId !== null && institutionId !== undefined) {
      body.institution_id = institutionId;
    }

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