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
    // Clear errors when user starts typing
    if (error) setError("");
    if (success) setSuccess("");
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setError("");
    setSuccess("");
    setLoading(true);

    try {
      // Validation
      if (!form.institution_id) {
        throw new Error("Please select an institution");
      }
      if (!form.email) {
        throw new Error("Please enter your email");
      }
      if (!form.password) {
        throw new Error("Please enter a password");
      }
      if (form.password.length < 6) {
        throw new Error("Password must be at least 6 characters");
      }
      if (!form.fullName.trim()) {
        throw new Error("Please enter your full name");
      }

      // Send data to backend - let backend handle name parsing
      const message = await createUser({
        institution_id: parseInt(form.institution_id),
        fullName: form.fullName.trim(),
        email: form.email.trim(),
        password: form.password
      });

      // Show success message
      setSuccess(message || "Account created successfully! Redirecting to login...");
      setForm({ fullName: "", email: "", password: "", institution_id: "" });

      // Redirect to login after 2 seconds
      setTimeout(() => {
        window.location.href = "/login";
      }, 2000);

    } catch (err) {
      console.error('Registration error:', err);
      setError(err.message || "Failed to create account. Please try again.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="login-container">
      <div className="login-left">
            <div className="login-left">
        <div className="bg-decoration">
          <div className="bg-circle bg-circle-1"></div>
          <div className="bg-circle bg-circle-2"></div>
        </div>
        <div className="brand-content">
          <div className="logo-wrapper">
            <div className="logo-glow"></div>
            <div className="logo-container">
              <img src="frontend\public\miams_logo.svg" alt="MIAMS Logo" className="logo-image" />
            </div>
          </div>
          <div className="brand-text">
            <h2 className="brand-title">MIAMS</h2>
            <p className="brand-subtitle">
              Multi-institutional Asset Management System
            </p>
          </div>
        </div>
      </div>  
      </div>
      <div className="login-right">
        <div className="form-wrapper">
          <div className="login-card">
            <div className="card-header">
              <h1 className="card-title">Create Account</h1>
              <p className="card-subtitle">Fill in your details to register</p>
            </div>

            {error && (
              <div className="error-message">
                <p>{error}</p>
              </div>
            )}
            
            {success && (
              <div className="success-message">
                <p>{success}</p>
              </div>
            )}

            <form onSubmit={handleSubmit} className="form-content">
              {/* Full Name */}
              <div className="form-field">
                <label className="field-label">Full Name</label>
                <div className="input-group">
                  <UserPlus className="input-icon" />
                  <input
                    type="text"
                    name="fullName"
                    value={form.fullName}
                    onChange={handleChange}
                    placeholder="Your full name"
                    className="input-field"
                    required
                    disabled={loading}
                  />
                </div>
              </div>

              {/* Email */}
              <div className="form-field">
                <label className="field-label">Email</label>
                <div className="input-group">
                  <Mail className="input-icon" />
                  <input
                    type="email"
                    name="email"
                    value={form.email}
                    onChange={handleChange}
                    placeholder="you@example.com"
                    className="input-field"
                    required
                    disabled={loading}
                  />
                </div>
              </div>

              {/* Password */}
              <div className="form-field">
                <label className="field-label">Password</label>
                <div className="input-group">
                  <Lock className="input-icon" />
                  <input
                    type="password"
                    name="password"
                    value={form.password}
                    onChange={handleChange}
                    placeholder="Enter password (min 6 characters)"
                    className="input-field"
                    required
                    disabled={loading}
                    minLength={6}
                  />
                </div>
              </div>

              {/* Institution */}
              <div className="form-field">
                <label className="field-label">Institution</label>
                <div className="input-group">
                  <Building className="input-icon" />
                  <select
                    name="institution_id"
                    value={form.institution_id}
                    onChange={handleChange}
                    className="input-field"
                    required
                    disabled={loading}
                  >
                    <option value="">Select your institution</option>
                    {institutions.map(inst => (
                      <option key={inst.id} value={inst.id}>
                        {inst.name}
                      </option>
                    ))}
                  </select>
                </div>
              </div>

              <button
                type="submit"
                disabled={loading}
                className="submit-button"
              >
                {loading ? "Creating..." : "Create Account"}
              </button>
            </form>

            <div className="divider">
              <span className="divider-text">Already have an account?</span>
            </div>
            <div className="signup-section">
              <a href="/login" className="signup-text">
                Sign in here
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}