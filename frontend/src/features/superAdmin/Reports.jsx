import { useState, useEffect } from "react";
import { 
  FileText, 
  Filter, 
  Download, 
  Search,
  Calendar,
  User,
  Building2,
  Activity,
  ChevronDown,
  X,
  AlertCircle,
  CheckCircle,
  Info,
  Shield
} from "lucide-react";
import "../../styles/SuperAdmin/AuditLogs.css";

export default function AuditLogs() {
  const [loading, setLoading] = useState(true);
  const [logs, setLogs] = useState([]);
  const [filteredLogs, setFilteredLogs] = useState([]);
  const [error, setError] = useState("");
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalRecords, setTotalRecords] = useState(0);
  
  const [filters, setFilters] = useState({
    user: "",
    institution: "",
    actionType: "",
    dateFrom: "",
    dateTo: "",
    entityType: "",
    searchQuery: ""
  });
  
  const [showFilters, setShowFilters] = useState(true);
  const [institutions, setInstitutions] = useState([]);
  const [users, setUsers] = useState([]);
  
  const actionTypes = [
    "CREATE", "UPDATE", "DELETE", "CHECK_OUT", "CHECK_IN", 
    "TRANSFER", "RETIRE", "LOGIN", "LOGOUT"
  ];
  
  const entityTypes = [
    "assets", "users", "maintenance_records", "transactions", "auth"
  ];

  useEffect(() => {
    fetchInstitutions();
    fetchUsers();
    fetchLogs();
  }, [currentPage]);

  useEffect(() => {
    applyFilters();
  }, [logs, filters]);

  const fetchInstitutions = async () => {
    try {
      const token = localStorage.getItem("token");
      const response = await fetch("http://localhost:8000/api/super_admin/institutions", {
        headers: { "Authorization": `Bearer ${token}` }
      });
      
      if (response.ok) {
        const data = await response.json();
        setInstitutions(data.institutions || []);
      }
    } catch (err) {
      console.error("Error fetching institutions:", err);
    }
  };

  const fetchUsers = async () => {
    try {
      const token = localStorage.getItem("token");
      const response = await fetch("http://localhost:8000/api/super_admin/users", {
        headers: { "Authorization": `Bearer ${token}` }
      });
      
      if (response.ok) {
        const data = await response.json();
        setUsers(data.users || []);
      }
    } catch (err) {
      console.error("Error fetching users:", err);
    }
  };

  const fetchLogs = async () => {
    setLoading(true);
    setError("");

    try {
      const token = localStorage.getItem("token");
      if (!token) {
        setError("No authentication token found");
        setLoading(false);
        return;
      }

      const response = await fetch(`http://localhost:8000/api/super_admin/audit-logs?page=${currentPage}&limit=50`, {
        method: "GET",
        headers: {
          "Authorization": `Bearer ${token}`,
          "Content-Type": "application/json"
        }
      });

      if (!response.ok) {
        throw new Error(`Failed to fetch logs: ${response.status}`);
      }

      const data = await response.json();
      setLogs(data.logs || []);
      setTotalPages(data.total_pages || 1);
      setTotalRecords(data.total_records || 0);
    } catch (err) {
      console.error("Fetch error:", err);
      setError(err.message || "Failed to load audit logs");
    } finally {
      setLoading(false);
    }
  };

  const applyFilters = () => {
    let filtered = [...logs];

    if (filters.user) {
      filtered = filtered.filter(log => 
        log.user_id === parseInt(filters.user)
      );
    }

    if (filters.institution) {
      filtered = filtered.filter(log => 
        log.institution_id === parseInt(filters.institution)
      );
    }

    if (filters.actionType) {
      filtered = filtered.filter(log => 
        log.action?.toUpperCase() === filters.actionType
      );
    }

    if (filters.entityType) {
      filtered = filtered.filter(log => 
        log.entity_type?.toLowerCase() === filters.entityType.toLowerCase()
      );
    }

    if (filters.dateFrom) {
      filtered = filtered.filter(log => 
        new Date(log.created_at) >= new Date(filters.dateFrom)
      );
    }

    if (filters.dateTo) {
      const endDate = new Date(filters.dateTo);
      endDate.setHours(23, 59, 59, 999);
      filtered = filtered.filter(log => 
        new Date(log.created_at) <= endDate
      );
    }

    if (filters.searchQuery) {
      const query = filters.searchQuery.toLowerCase();
      filtered = filtered.filter(log => 
        log.user_name?.toLowerCase().includes(query) ||
        log.institution_name?.toLowerCase().includes(query) ||
        log.action?.toLowerCase().includes(query) ||
        log.entity_type?.toLowerCase().includes(query) ||
        JSON.stringify(log.details || {}).toLowerCase().includes(query)
      );
    }

    setFilteredLogs(filtered);
  };

  const handleFilterChange = (key, value) => {
    setFilters(prev => ({ ...prev, [key]: value }));
  };

  const clearFilters = () => {
    setFilters({
      user: "",
      institution: "",
      actionType: "",
      dateFrom: "",
      dateTo: "",
      entityType: "",
      searchQuery: ""
    });
  };

  const exportToCSV = () => {
    const csvData = filteredLogs.map(log => ({
      Timestamp: formatDateTime(log.created_at),
      User: log.user_name || "Unknown",
      Institution: log.institution_name || "N/A",
      Action: log.action,
      EntityType: log.entity_type,
      EntityID: log.entity_id || "N/A",
      Details: JSON.stringify(log.details || {})
    }));

    const headers = Object.keys(csvData[0] || {});
    const csv = [
      headers.join(","),
      ...csvData.map(row => 
        headers.map(header => `"${row[header] || ""}"`).join(",")
      )
    ].join("\n");

    const blob = new Blob([csv], { type: "text/csv" });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `audit-logs-${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
  };

  const formatDateTime = (dateStr) => {
    if (!dateStr) return "N/A";
    const date = new Date(dateStr);
    return date.toLocaleString();
  };

  const getActionIcon = (action) => {
    switch (action?.toUpperCase()) {
      case "CREATE": return <CheckCircle className="action-icon-green" size={16} />;
      case "UPDATE": return <Info className="action-icon-blue" size={16} />;
      case "DELETE": return <AlertCircle className="action-icon-red" size={16} />;
      case "RETIRE": return <AlertCircle className="action-icon-orange" size={16} />;
      default: return <Activity className="action-icon-gray" size={16} />;
    }
  };

  const getActionClass = (action) => {
    switch (action?.toUpperCase()) {
      case "CREATE": return "action-badge-create";
      case "UPDATE": return "action-badge-update";
      case "DELETE": return "action-badge-delete";
      case "RETIRE": return "action-badge-retire";
      case "LOGIN": return "action-badge-login";
      default: return "action-badge-default";
    }
  };

  if (loading && logs.length === 0) {
    return (
      <div className="audit-loading-container">
        <div className="audit-spinner"></div>
        <p className="audit-loading-text">Loading audit logs...</p>
      </div>
    );
  }

  return (
    <div className="audit-logs-page">
      <style>{`
        .audit-logs-page {
          padding: 2rem;
          background-color: #f9fafb;
          min-height: 100vh;
        }

        .audit-header {
          margin-bottom: 1.5rem;
          display: flex;
          align-items: center;
          justify-content: space-between;
        }

        .audit-header-left {
          display: flex;
          align-items: center;
        }

        .audit-header-icon {
          color: #2563eb;
          margin-right: 0.75rem;
        }

        .audit-header h1 {
          font-size: 1.875rem;
          font-weight: bold;
          color: #111827;
        }

        .audit-header p {
          color: #6b7280;
          margin-top: 0.25rem;
        }

        .btn-export {
          display: flex;
          align-items: center;
          gap: 0.5rem;
          padding: 0.5rem 1rem;
          background: #2563eb;
          color: white;
          border: none;
          border-radius: 0.5rem;
          cursor: pointer;
          font-weight: 500;
          transition: background 0.2s;
        }

        .btn-export:hover {
          background: #1d4ed8;
        }

        .stats-grid {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
          gap: 1rem;
          margin-bottom: 1.5rem;
        }

        .stat-card {
          background: white;
          border-radius: 0.5rem;
          box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
          padding: 1rem;
          border-left: 4px solid #2563eb;
        }

        .stat-card.stat-green {
          border-left-color: #10b981;
        }

        .stat-card.stat-purple {
          border-left-color: #8b5cf6;
        }

        .stat-card.stat-orange {
          border-left-color: #f59e0b;
        }

        .stat-label {
          font-size: 0.875rem;
          color: #6b7280;
        }

        .stat-value {
          font-size: 1.5rem;
          font-weight: bold;
          color: #111827;
        }

        .filters-card {
          background: white;
          border-radius: 0.5rem;
          box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
          margin-bottom: 1.5rem;
        }

        .filters-header {
          padding: 1rem;
          border-bottom: 1px solid #e5e7eb;
          display: flex;
          align-items: center;
          justify-content: space-between;
          cursor: pointer;
        }

        .filters-header:hover {
          background: #f9fafb;
        }

        .filters-header-left {
          display: flex;
          align-items: center;
          gap: 0.5rem;
        }

        .filters-header h2 {
          font-size: 1.125rem;
          font-weight: 600;
          color: #111827;
        }

        .chevron-icon {
          color: #6b7280;
          transition: transform 0.2s;
        }

        .chevron-icon.rotated {
          transform: rotate(180deg);
        }

        .filters-body {
          padding: 1rem;
        }

        .filters-grid {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
          gap: 1rem;
          margin-bottom: 1rem;
        }

        .filter-group {
          display: flex;
          flex-direction: column;
        }

        .filter-label {
          display: block;
          font-size: 0.875rem;
          font-weight: 500;
          color: #374151;
          margin-bottom: 0.25rem;
        }

        .filter-input-wrapper {
          position: relative;
        }

        .filter-search-icon {
          position: absolute;
          left: 0.75rem;
          top: 50%;
          transform: translateY(-50%);
          color: #9ca3af;
        }

        .filter-input,
        .filter-select {
          width: 100%;
          padding: 0.5rem 0.75rem;
          border: 1px solid #d1d5db;
          border-radius: 0.5rem;
          font-size: 0.875rem;
        }

        .filter-input {
          padding-left: 2.5rem;
        }

        .filter-input:focus,
        .filter-select:focus {
          outline: none;
          border-color: #2563eb;
          box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .filters-actions {
          display: flex;
          justify-content: flex-end;
        }

        .btn-clear {
          display: flex;
          align-items: center;
          gap: 0.5rem;
          padding: 0.5rem 1rem;
          background: #f3f4f6;
          color: #374151;
          border: none;
          border-radius: 0.5rem;
          cursor: pointer;
          font-weight: 500;
          transition: background 0.2s;
        }

        .btn-clear:hover {
          background: #e5e7eb;
        }

        .logs-table-container {
          background: white;
          border-radius: 0.5rem;
          box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
          overflow: hidden;
        }

        .logs-table-wrapper {
          overflow-x: auto;
        }

        .logs-table {
          width: 100%;
          border-collapse: collapse;
        }

        .logs-table thead {
          background: #f9fafb;
          border-bottom: 1px solid #e5e7eb;
        }

        .logs-table th {
          padding: 0.75rem 1.5rem;
          text-align: left;
          font-size: 0.75rem;
          font-weight: 500;
          color: #6b7280;
          text-transform: uppercase;
          letter-spacing: 0.05em;
        }

        .logs-table tbody tr {
          border-bottom: 1px solid #e5e7eb;
          transition: background 0.15s;
        }

        .logs-table tbody tr:hover {
          background: #f9fafb;
        }

        .logs-table td {
          padding: 1rem 1.5rem;
          font-size: 0.875rem;
          color: #111827;
        }

        .user-cell,
        .institution-cell {
          display: flex;
          align-items: center;
          gap: 0.5rem;
        }

        .cell-icon {
          color: #9ca3af;
        }

        .user-name {
          font-weight: 500;
        }

        .action-badge {
          display: inline-flex;
          align-items: center;
          gap: 0.25rem;
          padding: 0.25rem 0.75rem;
          border-radius: 9999px;
          font-size: 0.75rem;
          font-weight: 500;
          border: 1px solid;
        }

        .action-badge-create {
          background: #d1fae5;
          color: #065f46;
          border-color: #a7f3d0;
        }

        .action-badge-update {
          background: #dbeafe;
          color: #1e40af;
          border-color: #bfdbfe;
        }

        .action-badge-delete {
          background: #fee2e2;
          color: #991b1b;
          border-color: #fecaca;
        }

        .action-badge-retire {
          background: #fed7aa;
          color: #92400e;
          border-color: #fdba74;
        }

        .action-badge-login {
          background: #ede9fe;
          color: #6b21a8;
          border-color: #ddd6fe;
        }

        .action-badge-default {
          background: #f3f4f6;
          color: #374151;
          border-color: #e5e7eb;
        }

        .action-icon-green { color: #059669; }
        .action-icon-blue { color: #2563eb; }
        .action-icon-red { color: #dc2626; }
        .action-icon-orange { color: #f59e0b; }
        .action-icon-gray { color: #6b7280; }

        .entity-cell {
          color: #374151;
        }

        .entity-id {
          color: #9ca3af;
        }

        .details-summary {
          font-weight: 500;
          color: #2563eb;
          cursor: pointer;
        }

        .details-summary:hover {
          color: #1d4ed8;
        }

        .details-content {
          margin-top: 0.5rem;
          padding: 0.5rem;
          background: #f9fafb;
          border-radius: 0.25rem;
          font-size: 0.75rem;
          overflow: auto;
          max-height: 8rem;
        }

        .empty-state {
          padding: 3rem 1.5rem;
          text-align: center;
        }

        .empty-icon {
          margin: 0 auto 0.75rem;
          color: #9ca3af;
        }

        .empty-title {
          color: #6b7280;
          font-weight: 500;
        }

        .empty-subtitle {
          color: #9ca3af;
          font-size: 0.875rem;
          margin-top: 0.25rem;
        }

        .pagination {
          padding: 1rem 1.5rem;
          border-top: 1px solid #e5e7eb;
          display: flex;
          align-items: center;
          justify-content: space-between;
        }

        .pagination-info {
          font-size: 0.875rem;
          color: #6b7280;
        }

        .pagination-buttons {
          display: flex;
          gap: 0.5rem;
        }

        .btn-pagination {
          padding: 0.5rem 1rem;
          border: 1px solid #d1d5db;
          background: white;
          border-radius: 0.5rem;
          cursor: pointer;
          transition: background 0.2s;
        }

        .btn-pagination:hover:not(:disabled) {
          background: #f9fafb;
        }

        .btn-pagination:disabled {
          opacity: 0.5;
          cursor: not-allowed;
        }

        .audit-loading-container {
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          height: 100vh;
        }

        .audit-spinner {
          width: 3rem;
          height: 3rem;
          border: 2px solid #e5e7eb;
          border-top-color: #2563eb;
          border-radius: 50%;
          animation: spin 0.8s linear infinite;
        }

        .audit-loading-text {
          margin-top: 1rem;
          color: #6b7280;
        }

        @keyframes spin {
          to { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
          .audit-logs-page {
            padding: 1rem;
          }

          .audit-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
          }

          .stats-grid {
            grid-template-columns: repeat(2, 1fr);
          }

          .filters-grid {
            grid-template-columns: 1fr;
          }

          .logs-table {
            min-width: 800px;
          }
        }
      `}</style>

      <div className="audit-header">
        <div className="audit-header-left">
          <Shield className="audit-header-icon" size={32} />
          <div>
            <h1>Audit & Activity Logs</h1>
            <p>Immutable record of all system activities</p>
          </div>
        </div>
        <button onClick={exportToCSV} className="btn-export">
          <Download size={18} />
          Export CSV
        </button>
      </div>

      <div className="stats-grid">
        <div className="stat-card">
          <p className="stat-label">Total Records</p>
          <p className="stat-value">{totalRecords}</p>
        </div>
        <div className="stat-card stat-green">
          <p className="stat-label">Filtered Results</p>
          <p className="stat-value">{filteredLogs.length}</p>
        </div>
        <div className="stat-card stat-purple">
          <p className="stat-label">Active Filters</p>
          <p className="stat-value">
            {Object.values(filters).filter(v => v !== "").length}
          </p>
        </div>
        <div className="stat-card stat-orange">
          <p className="stat-label">Current Page</p>
          <p className="stat-value">{currentPage} / {totalPages}</p>
        </div>
      </div>

      <div className="filters-card">
        <div className="filters-header" onClick={() => setShowFilters(!showFilters)}>
          <div className="filters-header-left">
            <Filter size={20} />
            <h2>Filters</h2>
          </div>
          <ChevronDown className={`chevron-icon ${showFilters ? 'rotated' : ''}`} size={20} />
        </div>
        
        {showFilters && (
          <div className="filters-body">
            <div className="filters-grid">
              <div className="filter-group">
                <label className="filter-label">Search</label>
                <div className="filter-input-wrapper">
                  <Search className="filter-search-icon" size={18} />
                  <input
                    type="text"
                    value={filters.searchQuery}
                    onChange={(e) => handleFilterChange("searchQuery", e.target.value)}
                    placeholder="Search logs..."
                    className="filter-input"
                  />
                </div>
              </div>

              <div className="filter-group">
                <label className="filter-label">User</label>
                <select
                  value={filters.user}
                  onChange={(e) => handleFilterChange("user", e.target.value)}
                  className="filter-select"
                >
                  <option value="">All Users</option>
                  {users.map(user => (
                    <option key={user.id} value={user.id}>
                      {user.first_name} {user.last_name}
                    </option>
                  ))}
                </select>
              </div>

              <div className="filter-group">
                <label className="filter-label">Institution</label>
                <select
                  value={filters.institution}
                  onChange={(e) => handleFilterChange("institution", e.target.value)}
                  className="filter-select"
                >
                  <option value="">All Institutions</option>
                  {institutions.map(inst => (
                    <option key={inst.id} value={inst.id}>
                      {inst.name}
                    </option>
                  ))}
                </select>
              </div>

              <div className="filter-group">
                <label className="filter-label">Action Type</label>
                <select
                  value={filters.actionType}
                  onChange={(e) => handleFilterChange("actionType", e.target.value)}
                  className="filter-select"
                >
                  <option value="">All Actions</option>
                  {actionTypes.map(action => (
                    <option key={action} value={action}>{action}</option>
                  ))}
                </select>
              </div>

              <div className="filter-group">
                <label className="filter-label">Entity Type</label>
                <select
                  value={filters.entityType}
                  onChange={(e) => handleFilterChange("entityType", e.target.value)}
                  className="filter-select"
                >
                  <option value="">All Types</option>
                  {entityTypes.map(type => (
                    <option key={type} value={type}>{type}</option>
                  ))}
                </select>
              </div>

              <div className="filter-group">
                <label className="filter-label">Date From</label>
                <input
                  type="date"
                  value={filters.dateFrom}
                  onChange={(e) => handleFilterChange("dateFrom", e.target.value)}
                  className="filter-select"
                />
              </div>

              <div className="filter-group">
                <label className="filter-label">Date To</label>
                <input
                  type="date"
                  value={filters.dateTo}
                  onChange={(e) => handleFilterChange("dateTo", e.target.value)}
                  className="filter-select"
                />
              </div>
            </div>

            <div className="filters-actions">
              <button onClick={clearFilters} className="btn-clear">
                <X size={18} />
                Clear Filters
              </button>
            </div>
          </div>
        )}
      </div>

      <div className="logs-table-container">
        <div className="logs-table-wrapper">
          <table className="logs-table">
            <thead>
              <tr>
                <th>Timestamp</th>
                <th>User</th>
                <th>Institution</th>
                <th>Action</th>
                <th>Entity</th>
                <th>Details</th>
              </tr>
            </thead>
            <tbody>
              {filteredLogs.length > 0 ? (
                filteredLogs.map((log, index) => (
                  <tr key={index}>
                    <td>{formatDateTime(log.created_at)}</td>
                    <td>
                      <div className="user-cell">
                        <User className="cell-icon" size={16} />
                        <span className="user-name">{log.user_name || "Unknown"}</span>
                      </div>
                    </td>
                    <td>
                      <div className="institution-cell">
                        <Building2 className="cell-icon" size={16} />
                        <span>{log.institution_name || "N/A"}</span>
                      </div>
                    </td>
                    <td>
                      <span className={`action-badge ${getActionClass(log.action)}`}>
                        {getActionIcon(log.action)}
                        <span>{log.action}</span>
                      </span>
                    </td>
                    <td className="entity-cell">
                      {log.entity_type}
                      {log.entity_id && <span className="entity-id"> #{log.entity_id}</span>}
                    </td>
                    <td>
                      <details>
                        <summary className="details-summary">View Details</summary>
                        <pre className="details-content">
                          {JSON.stringify(log.details || {}, null, 2)}
                        </pre>
                      </details>
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan="6">
                    <div className="empty-state">
                      <FileText className="empty-icon" size={48} />
                      <p className="empty-title">No audit logs found</p>
                      <p className="empty-subtitle">Try adjusting your filters</p>
                    </div>
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>

        {totalPages > 1 && (
          <div className="pagination">
            <div className="pagination-info">
              Showing page {currentPage} of {totalPages}
            </div>
            <div className="pagination-buttons">
              <button
                onClick={() => setCurrentPage(p => Math.max(1, p - 1))}
                disabled={currentPage === 1}
                className="btn-pagination"
              >
                Previous
              </button>
              <button
                onClick={() => setCurrentPage(p => Math.min(totalPages, p + 1))}
                disabled={currentPage === totalPages}
                className="btn-pagination"
              >
                Next
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}