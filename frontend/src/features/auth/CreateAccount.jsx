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

      const names = form.fullName.trim().split(" ");
      const first_name = names[0] || "";
      const last_name = names.slice(1).join(" ") || "";
      const username = form.fullName.replace(/\s+/g, "").toLowerCase();

      // Call createUser and ignore token
      await createUser({
        institution_id: parseInt(form.institution_id),
        username,
        email: form.email,
        password: form.password,
        first_name,
        last_name
      });

      setSuccess("Account created successfully! Please login.");
      setForm({ fullName: "", email: "", password: "", institution_id: "" });
    } catch (err) {
      setError(err.message || "Failed to create account");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="login-container">
      <div className="login-left">
        <div className="bg-decoration">
          <div className="bg-circle bg-circle-1"></div>
          <div className="bg-circle bg-circle-2"></div>
        </div>
        <div className="brand-content">
          <div className="logo-wrapper">
            <div className="logo-glow"></div>
            <div className="logo-container">
              <img src="/miams_logo.svg" alt="MIAMS Logo" className="logo-image" />
            </div>
          </div>
          <div className="brand-text">
            <h2 className="brand-title">MIAMS</h2>
            <p className="brand-subtitle">Multi-institutional Asset Management System</p>
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

            {error && <div className="error-message"><p>{error}</p></div>}
            {success && <div className="success-message"><p>{success}</p></div>}

            <div className="form-content">
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
                  />
                </div>
              </div>

              {/* Email */}
              <div className="form-field">
                <label className="field-label">Email Address</label>
                <div className="input-group">
                  <Mail className="input-icon" />
                  <input
                    type="email"
                    name="email"
                    value={form.email}
                    onChange={handleChange}
                    placeholder="you@example.com"
                    className="input-field"
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
                    placeholder="Enter password"
                    className="input-field"
                    required
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
                  >
                    <option value="">Select your institution</option>
                    <option value="1">Nairobi University</option>
                    <option value="2">Kampala Institute of Technology</option>
                    <option value="3">Dar es Salaam Polytechnic</option>
                  </select>
                </div>
              </div>

              <button onClick={handleSubmit} disabled={loading} className="submit-button">
                {loading ? "Creating..." : "Create Account"}
              </button>
            </div>

            <div className="divider"><span className="divider-text">Already have an account?</span></div>
            <div className="signup-section">
              <a href="/" className="signup-text">Sign in here</a>
            </div>
          </div>

          <p className="footer-text">Protected by industry-standard encryption</p>
        </div>
      </div>
    </div>
  );
}
