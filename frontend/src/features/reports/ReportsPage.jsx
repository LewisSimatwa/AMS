import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import "../../styles/Reports.css";

export default function Reports() {
  const navigate = useNavigate();
  const [filters, setFilters] = useState({
    status: "All",
    startDate: "",
    endDate: "",
  });
  const [auditLogs, setAuditLogs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [generating, setGenerating] = useState(false);

  const token = localStorage.getItem("token");
  const institutionId = localStorage.getItem("institutionId");

  useEffect(() => {
    if (!token || !institutionId) {
      navigate("/login");
      return;
    }

    fetchAuditLogs();
  }, [token, institutionId]);

  async function fetchAuditLogs() {
    setLoading(true);
    setError("");

    try {
      const headers = {
        Authorization: `Bearer ${token}`,
        "X-Institution-ID": institutionId,
      };

      const response = await fetch(
        `http://localhost:8000/api/audit/logs?institution_id=${institutionId}`,
        { headers }
      );

      if (!response.ok) {
        throw new Error("Failed to fetch audit logs");
      }

      const data = await response.json();
      setAuditLogs(data.logs || []);
    } catch (err) {
      console.error("Fetch audit logs error:", err);
      setError(err.message || "Failed to load audit logs");
    } finally {
      setLoading(false);
    }
  }

  function handleFilterChange(e) {
    const { name, value } = e.target;
    setFilters((prev) => ({ ...prev, [name]: value }));
  }

  // Filter audit logs
  const filteredLogs = auditLogs.filter((log) => {
    const logDate = new Date(log.created_at);
    const startDate = filters.startDate ? new Date(filters.startDate) : null;
    const endDate = filters.endDate ? new Date(filters.endDate) : null;

    const statusMatch = filters.status === "All" || 
      (filters.status === "Movement" && log.action === "Asset Movement") ||
      (filters.status === "Status" && log.action === "Status Change");

    const dateMatch = 
      (!startDate || logDate >= startDate) &&
      (!endDate || logDate <= endDate);

    return statusMatch && dateMatch;
  });

  async function generateReport() {
    setGenerating(true);

    try {
      const headers = {
        Authorization: `Bearer ${token}`,
        "X-Institution-ID": institutionId,
      };

      const response = await fetch(
        `http://localhost:8000/api/reports/generate?institution_id=${institutionId}`,
        { headers }
      );

      if (!response.ok) {
        throw new Error("Failed to generate report");
      }

      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `asset_report_${new Date().toISOString().split("T")[0]}.pdf`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    } catch (err) {
      console.error("Generate report error:", err);
      alert(err.message || "Failed to generate report");
    } finally {
      setGenerating(false);
    }
  }

  if (loading) {
    return (
      <div className="reports-page">
        <div className="loading-spinner">Loading reports...</div>
      </div>
    );
  }

  return (
    <div className="reports-page">
      <div className="reports-header">
        <div>
          <h1>Reports & Audit Logs</h1>
          <p>View activity logs and generate reports</p>
        </div>
        <button 
          className="generate-btn" 
          onClick={generateReport}
          disabled={generating}
        >
          {generating ? "Generating..." : "Generate PDF Report"}
        </button>
      </div>

      {error && (
        <div className="error-banner">
          <p>{error}</p>
          <button onClick={fetchAuditLogs}>Retry</button>
        </div>
      )}

      {/* Filters */}
      <div className="reports-filters">
        <select name="status" value={filters.status} onChange={handleFilterChange}>
          <option value="All">All Activities</option>
          <option value="Movement">Asset Movement</option>
          <option value="Status">Status Changes</option>
        </select>

        <input
          type="date"
          name="startDate"
          value={filters.startDate}
          onChange={handleFilterChange}
          placeholder="Start Date"
        />

        <input
          type="date"
          name="endDate"
          value={filters.endDate}
          onChange={handleFilterChange}
          placeholder="End Date"
        />

        <button onClick={fetchAuditLogs} className="refresh-btn">
          Refresh
        </button>
      </div>

      {/* Audit Logs Table */}
      <div className="reports-summary">
        <p>
          Showing <strong>{filteredLogs.length}</strong> of{" "}
          <strong>{auditLogs.length}</strong> activities
        </p>
      </div>

      <table className="reports-table">
        <thead>
          <tr>
            <th>Action</th>
            <th>Details</th>
            <th>User</th>
            <th>Date & Time</th>
          </tr>
        </thead>
        <tbody>
          {filteredLogs.length === 0 ? (
            <tr>
              <td colSpan="4" style={{ textAlign: "center" }}>
                No activities found
              </td>
            </tr>
          ) : (
            filteredLogs.map((log) => (
              <tr key={log.id}>
                <td>
                  <span className="action-badge">{log.action}</span>
                </td>
                <td>{log.details || "—"}</td>
                <td>{log.username || "System"}</td>
                <td>{new Date(log.created_at).toLocaleString()}</td>
              </tr>
            ))
          )}
        </tbody>
      </table>
    </div>
  );
}