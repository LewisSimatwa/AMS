import { useEffect, useState } from "react";
import { Building2, ChevronDown, CheckCircle2, ArrowRight, Loader2 } from "lucide-react";
import { getInstitutions } from "../../api/institutions";
import "../../styles/selectinstitution.css";

export default function SelectInstitution() {
  const [institutions, setInstitutions] = useState([]);
  const [selected, setSelected] = useState("");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    async function fetchInstitutions() {
      try {
        const data = await getInstitutions();
        setInstitutions(data);
      } catch (err) {
        setError(err.message || "Failed to load institutions");
      } finally {
        setLoading(false);
      }
    }

    fetchInstitutions();
  }, []);

  function handleSelect(institutionId) {
    setSelected(institutionId);
  }

  async function handleSubmit() {
    if (!selected) return;
    setSubmitting(true);

    // Save selected institution and user to localStorage
    const user = JSON.parse(localStorage.getItem("user"));
    if (!user) {
      alert("User not found. Please log in again.");
      window.location.href = "/login";
      return;
    }

    localStorage.setItem("institution", JSON.stringify({ id: selected }));
    localStorage.setItem("user", JSON.stringify(user));

    // Redirect to dashboard
    setTimeout(() => {
      window.location.href = "/dashboard";
    }, 300);
  }

  if (loading) {
    return (
      <div className="select-institution-container">
        <div className="loading-container">
          <Loader2 className="loading-icon" size={48} />
          <p className="loading-text">Loading institutions...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="select-institution-container">
        <div className="error-container">
          <div className="error-icon">⚠️</div>
          <h2 className="error-title">Oops! Something went wrong</h2>
          <p className="error-message">{error}</p>
          <button 
            onClick={() => window.location.reload()} 
            className="retry-button"
          >
            Try Again
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="select-institution-container">
      <div className="content-wrapper">
        {/* Header Section */}
        <div className="header-section">
          <div className="logo-badge">
            <Building2 size={32} />
          </div>
          <h1 className="page-title">Select Your Institution</h1>
          <p className="page-subtitle">
            Choose the institution you want to access
          </p>
        </div>

        {/* Institutions Grid */}
        <div className="institutions-grid">
          {institutions.map((inst) => (
            <button
              key={inst.id}
              onClick={() => handleSelect(inst.id)}
              className={`institution-card ${selected === inst.id ? 'selected' : ''}`}
            >
              <div className="institution-icon">
                <Building2 size={24} />
              </div>
              <div className="institution-info">
                <h3 className="institution-name">{inst.name}</h3>
                {inst.location && (
                  <p className="institution-location">{inst.location}</p>
                )}
              </div>
              {selected === inst.id && (
                <div className="check-icon">
                  <CheckCircle2 size={24} />
                </div>
              )}
            </button>
          ))}
        </div>

        {/* Action Button */}
        <button
          onClick={handleSubmit}
          disabled={!selected || submitting}
          className="continue-button"
        >
          {submitting ? (
            <>
              <Loader2 className="button-spinner" size={20} />
              <span>Loading...</span>
            </>
          ) : (
            <>
              <span>Continue to Dashboard</span>
              <ArrowRight className="button-arrow" size={20} />
            </>
          )}
        </button>

        {/* Helper Text */}
        {!selected && (
          <p className="helper-text">
            Please select an institution to continue
          </p>
        )}
      </div>
    </div>
  );
}
