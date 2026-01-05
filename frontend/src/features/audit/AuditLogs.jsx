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

        console.log("Fetching from:", "http://localhost:8000/api/audit_logs.php");

        const res = await fetch("http://localhost:8000/api/audit_logs.php", {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        });

        console.log("Response status:", res.status);
        console.log("Response ok:", res.ok);

        // Check if response is ok before parsing JSON
        if (!res.ok) {
          const data = await res.json().catch(() => ({ error: 'Server error' }));
          throw new Error(data.error || `HTTP ${res.status}`);
        }

        const data = await res.json();
        console.log("Data received:", data);
        
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

  if (loading) return <p className="audit-loading">Loading audit logs…</p>;
  if (error) return <p className="audit-error">{error}</p>;

  return (
    <div className="audit-container">
      <h1 className="audit-title">Audit Logs</h1>

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
                <td>{new Date(log.timestamp).toLocaleString()}</td>
                <td>{log.performed_by}</td>
                <td className={`action ${log.action.toLowerCase()}`}>
                  {log.action}
                </td>
                <td>{log.entity_type}</td>
                <td>{log.entity_id}</td>
                <td className="old-value">{log.old_value || "-"}</td>
                <td className="new-value">{log.new_value || "-"}</td>
                <td>{log.institution_name}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}