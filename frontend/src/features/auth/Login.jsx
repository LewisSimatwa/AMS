import { useState } from "react";
import { Eye, EyeOff, Mail, Lock, LogIn, Building } from "lucide-react";
import { Link, useNavigate } from "react-router-dom";
import { loginUser } from "../../api/loginUser";
import "../../styles/login.css";

export default function Login() {
  const navigate = useNavigate();
  const [form, setForm] = useState({ 
    email: "", 
    password: "",
    institution_id: "" 
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [isSuperAdmin, setIsSuperAdmin] = useState(false);
  const [institutions, setInstitutions] = useState([
    { id: "1", name: "Nairobi University" },
    { id: "2", name: "Kampala Institute of Technology" },
    { id: "3", name: "Dar es Salaam Polytechnic" }
  ]);

  function handleChange(e) {
    setForm({ ...form, [e.target.name]: e.target.value });
  }

  // Detect if email is super admin
  function handleEmailChange(e) {
    const email = e.target.value;
    setForm({ ...form, email });
    
    // Check if this looks like a super admin email
    if (email.includes('super@') || email === 'super@miams.system') {
      setIsSuperAdmin(true);
      setForm(prev => ({ ...prev, institution_id: "" }));
    } else {
      setIsSuperAdmin(false);
    }
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setError("");
    setLoading(true);

    try {
      // Call login API with email, password, and institution_id (if not super admin)
      const response = await loginUser(
        form.email, 
        form.password, 
        isSuperAdmin ? null : parseInt(form.institution_id, 10)
      );
      
      // Store data in sessionStorage
      localStorage.setItem("token", response.token);
      localStorage.setItem("user", JSON.stringify(response.user));
      if (!isSuperAdmin) {
        localStorage.setItem("institutionId", parseInt(form.institution_id, 10));
      }
      
      // Route based on user role
      const userRole = response.user.role?.toLowerCase();
      
      if (userRole === 'super_admin') {
        navigate("/super-admin/dashboard");
      } else if (userRole === 'admin') {
        navigate("/dashboard");
      } else {
        navigate("/dashboard");
      }
    } catch (err) {
      setError(err.message || "Login failed");
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
            <p className="brand-subtitle">
              Multi-institutional Asset Management System
            </p>
          </div>
        </div>
      </div>

      <div className="login-right">
        <div className="form-wrapper">
          <div className="login-card">
            <div className="card-header">
              <h1 className="card-title">Welcome Back</h1>
              <p className="card-subtitle">Sign in to access your account</p>
            </div>

            {error && (
              <div className="error-message">
                <p>{error}</p>
              </div>
            )}

            <form className="form-content" onSubmit={handleSubmit}>
              <div className="form-field">
                <label className="field-label">Email Address</label>
                <div className="input-group">
                  <Mail className="input-icon" size={20} strokeWidth={2} />
                  <input
                    type="email"
                    name="email"
                    value={form.email}
                    onChange={handleEmailChange}
                    placeholder="you@example.com"
                    className="input-field"
                    required
                  />
                </div>
              </div>

              {!isSuperAdmin && (
                <div className="form-field">
                  <label className="field-label">Institution</label>
                  <div className="input-group">
                    <Building className="input-icon" size={20} strokeWidth={2} />
                    <select
                      name="institution_id"
                      value={form.institution_id}
                      onChange={handleChange}
                      className="input-field"
                      required
                    >
                      <option value="" disabled>
                        Select an institution
                      </option>
                      {institutions.map((inst) => (
                        <option key={inst.id} value={inst.id}>
                          {inst.name}
                        </option>
                      ))}
                    </select>
                  </div>
                </div>
              )}

              {isSuperAdmin && (
                <div className="form-field">
                  <p style={{ color: '#10b981', fontSize: '0.875rem', marginTop: '0.5rem' }}>
                    Super Admin login detected - no institution required
                  </p>
                </div>
              )}

              <div className="form-field">
                <label className="field-label">Password</label>
                <div className="input-group">
                  <Lock className="input-icon" size={20} strokeWidth={2} />
                  <input
                    type={showPassword ? "text" : "password"}
                    name="password"
                    value={form.password}
                    onChange={handleChange}
                    placeholder="Enter your password"
                    className="input-field input-field-password"
                    required
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="password-toggle"
                  >
                    {showPassword ? <EyeOff size={20} /> : <Eye size={20} />}
                  </button>
                </div>
              </div>

              <div className="form-options">
                <label className="checkbox-label">
                  <input type="checkbox" className="checkbox-input" />
                  <span>Remember me</span>
                </label>
                <Link to="/forgot-password" className="forgot-link">
                  Forgot password?
                </Link>
              </div>

              <button
                type="submit"
                disabled={loading}
                className="submit-button"
              >
                {loading ? (
                  <>
                    <div className="loading-spinner"></div>
                    <span>Signing in...</span>
                  </>
                ) : (
                  <>
                    <span>Sign In</span>
                    <LogIn className="button-icon" size={20} />
                  </>
                )}
              </button>
            </form>

            <div className="divider">
              <span className="divider-text">New to MIAMS?</span>
            </div>

            <div className="signup-section">
              <Link to="/create-account" className="signup-text">
                Don't have an account? <span className="signup-link">Create one now</span>
              </Link>
            </div>
          </div>

          <p className="footer-text">
            Protected by industry-standard encryption
          </p>
        </div>
      </div>
    </div>
  );
}