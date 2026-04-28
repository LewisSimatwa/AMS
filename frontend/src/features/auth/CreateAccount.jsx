import { useState } from "react";
import { Mail, Lock, UserPlus, User } from "lucide-react";
import { createUser } from "../../api/auth"; 
import "../../styles/AuthPages.css";

export default function CreateAccount() {
  const [form, setForm] = useState({
    firstName: "",
    lastName: "",
    email: "",
    password: "",
    confirmPassword: ""
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");

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
      if (!form.firstName.trim()) {
        throw new Error("Please enter your first name");
      }
      if (!form.lastName.trim()) {
        throw new Error("Please enter your last name");
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
      if (!form.confirmPassword) {
        throw new Error("Please confirm your password");
      }
      if (form.password !== form.confirmPassword) {
        throw new Error("Passwords do not match");
      }

      // Send data to backend - backend will validate domain
      const message = await createUser({
        firstName: form.firstName.trim(),
        lastName: form.lastName.trim(),
        email: form.email.trim(),
        password: form.password
      });

      // Show success message
      setSuccess(message || "Account created successfully! Redirecting to login...");
      setForm({ firstName: "", lastName: "", email: "", password: "", confirmPassword: "" });

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
        <div className="bg-decoration">
          <div className="bg-circle bg-circle-1"></div>
          <div className="bg-circle bg-circle-2"></div>
        </div>
        <div className="brand-content">
          <div className="logo-wrapper">
            <div className="logo-glow"></div>
            <div className="logo-container">
              <img src="frontend\public\amslogo.png" alt="MIAMS Logo" className="logo-image" />
            </div>
          </div>
          <div className="brand-text">
            <h2 className="brand-title">AMS</h2>
            <p className="brand-subtitle">
              Asset Management System
            </p>
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
              {/* First Name */}
              <div className="form-field">
                <label className="field-label">First Name</label>
                <div className="input-group">
                  <User className="input-icon" />
                  <input
                    type="text"
                    name="firstName"
                    value={form.firstName}
                    onChange={handleChange}
                    placeholder="Your first name"
                    className="input-field"
                    required
                    disabled={loading}
                  />
                </div>
              </div>

              {/* Last Name */}
              <div className="form-field">
                <label className="field-label">Last Name</label>
                <div className="input-group">
                  <UserPlus className="input-icon" />
                  <input
                    type="text"
                    name="lastName"
                    value={form.lastName}
                    onChange={handleChange}
                    placeholder="Your last name"
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
                    placeholder="you@yourinstitution.edu"
                    className="input-field"
                    required
                    disabled={loading}
                  />
                </div>
                <p className="field-hint">Use your institutional email address</p>
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

              {/* Confirm Password */}
              <div className="form-field">
                <label className="field-label">Confirm Password</label>
                <div className="input-group">
                  <Lock className="input-icon" />
                  <input
                    type="password"
                    name="confirmPassword"
                    value={form.confirmPassword}
                    onChange={handleChange}
                    placeholder="Re-enter your password"
                    className="input-field"
                    required
                    disabled={loading}
                  />
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