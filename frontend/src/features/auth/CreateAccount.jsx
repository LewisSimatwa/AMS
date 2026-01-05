import { useState } from "react";
import { Mail, Lock, UserPlus, Building } from "lucide-react";
import { createUser } from "../../api/auth"; 
import "../../styles/AuthPages.css";

export default function CreateAccount() {
  const [form, setForm] = useState({
    fullName: "",
    email: "",
    password: "",
    institution_id: ""
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");

  const institutions = [
    { id: "1", name: "Nairobi University" },
    { id: "2", name: "Kampala Institute of Technology" },
    { id: "3", name: "Dar es Salaam Polytechnic" }
  ];

  function handleChange(e) {
    setForm({ ...form, [e.target.name]: e.target.value });
  }


  async function handleSubmit(e) {
  e.preventDefault();
  setError("");
  setSuccess("");
  setLoading(true);

  try {
    if (!form.institution_id) throw new Error("Please select an institution");
    if (!form.email) throw new Error("Please enter your email");
    if (!form.password) throw new Error("Please enter a password");
    if (!form.fullName) throw new Error("Please enter your full name");

    const names = form.fullName.trim().split(" ");
    const first_name = names[0] || "";
    const last_name = names.slice(1).join(" ") || "";
    const username = form.fullName.replace(/\s+/g, "").toLowerCase();

    // Capture the response message
    const message = await createUser({
      institution_id: parseInt(form.institution_id),
      username,
      email: form.email.trim(),
      password: form.password,
      first_name,
      last_name
    });

    // Use the returned message
    setSuccess(message || "Account created successfully! Redirecting to login...");
    setForm({ fullName: "", email: "", password: "", institution_id: "" });

    // Redirect to login after 2 seconds
    setTimeout(() => window.location.href = "/", 2000);

  } catch (err) {
    setError(err.message || "Failed to create account");
  } finally {
    setLoading(false);
  }
}

  return (
    <div className="login-container">
      <div className="login-left">{/* Branding section */}</div>
      <div className="login-right">
        <div className="form-wrapper">
          <div className="login-card">
            <div className="card-header">
              <h1 className="card-title">Create Account</h1>
              <p className="card-subtitle">Fill in your details to register</p>
            </div>

            {error && <div className="error-message"><p>{error}</p></div>}
            {success && <div className="success-message"><p>{success}</p></div>}

            <div className="form-content">
              {/* Full Name */}
              <div className="form-field">
                <label className="field-label">Full Name</label>
                <div className="input-group">
                  <UserPlus className="input-icon" />
                  <input type="text" name="fullName" value={form.fullName} onChange={handleChange} placeholder="Your full name" className="input-field" required />
                </div>
              </div>

              {/* Email */}
              <div className="form-field">
                <label className="field-label">Email</label>
                <div className="input-group">
                  <Mail className="input-icon" />
                  <input type="email" name="email" value={form.email} onChange={handleChange} placeholder="you@example.com" className="input-field" required />
                </div>
              </div>

              {/* Password */}
              <div className="form-field">
                <label className="field-label">Password</label>
                <div className="input-group">
                  <Lock className="input-icon" />
                  <input type="password" name="password" value={form.password} onChange={handleChange} placeholder="Enter password" className="input-field" required />
                </div>
              </div>

              {/* Institution */}
              <div className="form-field">
                <label className="field-label">Institution</label>
                <div className="input-group">
                  <Building className="input-icon" />
                  <select name="institution_id" value={form.institution_id} onChange={handleChange} className="input-field" required>
                    <option value="">Select your institution</option>
                    {institutions.map(inst => (
                      <option key={inst.id} value={inst.id}>{inst.name}</option>
                    ))}
                  </select>
                </div>
              </div>

              <button onClick={handleSubmit} disabled={loading} className="submit-button">{loading ? "Creating..." : "Create Account"}</button>
            </div>

            <div className="divider"><span className="divider-text">Already have an account?</span></div>
            <div className="signup-section">
              <a href="/" className="signup-text">Sign in here</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
