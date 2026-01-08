import { useEffect, useState } from "react";
import "../../styles/AuditLogs.css";

export default function AuditLogs() {
  const [logs, setLogs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    const fetchAuditLogs = async () => {
      try {
        const token = localStorage.getItem("token");
        console.log("Token:", token ? "exists" : "missing");
        
        if (!token) throw new Error("Not authenticated");

        // Fixed URL - removed /api/ path
        const url = "http://localhost:8000/audit_logs.php";
        console.log("Fetching from:", url);

        const res = await fetch(url, {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        });

        console.log("Response status:", res.status);
        console.log("Response headers:", res.headers.get("content-type"));

        // Get response text first to see what's actually returned
        const text = await res.text();
        console.log("Raw response:", text.substring(0, 200));

        // Check if response is ok
        if (!res.ok) {
          let errorData;
          try {
            errorData = JSON.parse(text);
          } catch {
            errorData = { error: `HTTP ${res.status}: ${text.substring(0, 100)}` };
          }
          throw new Error(errorData.error || `HTTP ${res.status}`);
        }

        // Parse JSON
        let data;
        try {
          data = JSON.parse(text);
        } catch (parseError) {
          console.error("JSON parse error:", parseError);
          console.error("Response text:", text);
          throw new Error("Server returned invalid JSON: " + text.substring(0, 100));
        }

        console.log("Data received:", data);
        
        if (data.error) {
          throw new Error(data.error);
        }

        setLogs(data.logs || []);
      } catch (err) {
        console.error("Full error:", err);
        setError(err.message || "Failed to fetch");
      } finally {
        setLoading(false);
      }
    };

    fetchAuditLogs();
  }, []);

  // Helper function to format JSON values
  const formatValue = (value) => {
    if (!value) return "-";
    
    try {
      const parsed = JSON.parse(value);
      return JSON.stringify(parsed, null, 2);
    } catch {
      return value;
    }
  };

  if (loading) return <p className="audit-loading">Loading audit logs…</p>;
  if (error) return (
    <div className="audit-error-container">
      <p className="audit-error">{error}</p>
      <button onClick={() => window.location.reload()}>Retry</button>
    </div>
  );

  return (
    <div className="audit-container">
      <div className="audit-header">
        <h1 className="audit-title">📋 Audit Logs</h1>
        <p className="audit-subtitle">Track all system activities and changes</p>
      </div>

      <div className="audit-stats">
        <div className="stat-box">
          <span className="stat-label">Total Logs</span>
          <span className="stat-value">{logs.length}</span>
        </div>
        <div className="stat-box">
          <span className="stat-label">Today</span>
          <span className="stat-value">
            {logs.filter(log => {
              const logDate = new Date(log.timestamp);
              const today = new Date();
              return logDate.toDateString() === today.toDateString();
            }).length}
          </span>
        </div>
      </div>

      <div className="audit-table-wrapper">
        <table className="audit-table">
          <thead>
            <tr>
              <th>Timestamp</th>
              <th>Performed By</th>
              <th>Action</th>
              <th>Entity Type</th>
              <th>Entity ID</th>
              <th>Old Value</th>
              <th>New Value</th>
              <th>Institution</th>
            </tr>
          </thead>

          <tbody>
            {logs.length === 0 && (
              <tr>
                <td colSpan="8" className="no-data">
                  No audit logs found
                </td>
              </tr>
            )}

            {logs.map((log) => (
              <tr key={log.id}>
                <td className="timestamp-cell">
                  {new Date(log.timestamp).toLocaleString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                  })}
                </td>
                <td className="user-cell">{log.performed_by}</td>
                <td className={`action-cell action-${log.action.toLowerCase()}`}>
                  <span className="action-badge">{log.action}</span>
                </td>
                <td className="entity-cell">{log.entity_type || "-"}</td>
                <td className="entity-id-cell">{log.entity_id || "-"}</td>
                <td className="value-cell old-value">
                  <pre>{formatValue(log.old_value)}</pre>
                </td>
                <td className="value-cell new-value">
                  <pre>{formatValue(log.new_value)}</pre>
                </td>
                <td className="institution-cell">{log.institution_name}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}