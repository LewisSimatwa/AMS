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
  TrendingUp,
  BarChart3,
  PieChart as PieChartIcon,
  Award,
  AlertCircle
} from "lucide-react";
import { 
  LineChart, 
  Line, 
  BarChart, 
  Bar, 
  PieChart, 
  Pie, 
  Cell,
  XAxis, 
  YAxis, 
  CartesianGrid, 
  Tooltip, 
  Legend, 
  ResponsiveContainer 
} from "recharts";
import "../../styles/SuperAdmin/ReportsAndAudit.css";

export default function ReportsAndAudit() {
  const [activeSection, setActiveSection] = useState("analytics");
  
  // Analytics State
  const [analyticsData, setAnalyticsData] = useState({
    assetGrowth: [],
    retirementStats: {},
    institutionComparison: [],
    adminActivity: [],
    totalAssets: 0,
    activeAssets: 0,
    retiredAssets: 0
  });

  // Audit Logs State
  const [logs, setLogs] = useState([]);
  const [institutions, setInstitutions] = useState([]);
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  const [filters, setFilters] = useState({
    institution_id: "",
    user_id: "",
    action_type: "",
    entity_type: "",
    date_from: "",
    date_to: "",
    search: ""
  });

  const [pagination, setPagination] = useState({
    page: 1,
    limit: 50,
    total: 0
  });

  const actionTypes = [
    "LOGIN", "CREATE", "UPDATE", "DELETE", "CHECK_OUT", 
    "CHECK_IN", "TRANSFER", "RETIRE", "PASSWORD_RESET", "REVOKE_ADMIN"
  ];
  
  const entityTypes = [
    "assets", "users", "institutions", "maintenance_records", 
    "transactions", "auth"
  ];

  const COLORS = ['#667eea', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];

  useEffect(() => {
    fetchInstitutions();
    fetchUsers();
    if (activeSection === "analytics") {
      fetchAnalyticsData();
    } else {
      fetchLogs();
    }
  }, [activeSection, filters, pagination.page]);

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
      console.error("Failed to fetch institutions:", err);
    }
  };

  const fetchUsers = async () => {
    try {
      const token = localStorage.getItem("token");
      const response = await fetch("http://localhost:8000/api/super_admin/admins", {
        headers: { "Authorization": `Bearer ${token}` }
      });

      if (response.ok) {
        const data = await response.json();
        setUsers(data.admins || []);
      }
    } catch (err) {
      console.error("Failed to fetch users:", err);
    }
  };

  const fetchAnalyticsData = async () => {
    setLoading(true);
    setError("");

    try {
      const token = localStorage.getItem("token");
      const response = await fetch("http://localhost:8000/api/super_admin/analytics", {
        headers: { "Authorization": `Bearer ${token}` }
      });

      if (!response.ok) {
        throw new Error("Failed to fetch analytics data");
      }

      const data = await response.json();
      setAnalyticsData(data);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const fetchLogs = async () => {
    setLoading(true);
    setError("");

    try {
      const token = localStorage.getItem("token");
      const queryParams = new URLSearchParams({
        page: pagination.page,
        limit: pagination.limit,
        ...Object.fromEntries(Object.entries(filters).filter(([_, v]) => v !== ""))
      });

      const response = await fetch(`http://localhost:8000/api/super_admin/audit-logs?${queryParams}`, {
        headers: { "Authorization": `Bearer ${token}` }
      });

      if (!response.ok) {
        throw new Error("Failed to fetch audit logs");
      }

      const data = await response.json();
      setLogs(data.logs || []);
      setPagination(prev => ({ ...prev, total: data.total || 0 }));
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const exportAnalyticsReport = async () => {
    try {
      const token = localStorage.getItem("token");
      const response = await fetch("http://localhost:8000/api/super_admin/analytics/export", {
        headers: { "Authorization": `Bearer ${token}` }
      });

      if (!response.ok) {
        throw new Error("Failed to export report");
      }

      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `analytics_report_${new Date().toISOString().split('T')[0]}.pdf`;
      a.click();
      window.URL.revokeObjectURL(url);
    } catch (err) {
      setError(err.message);
    }
  };

  const exportAuditLogs = async () => {
    try {
      const token = localStorage.getItem("token");
      const queryParams = new URLSearchParams(
        Object.fromEntries(Object.entries(filters).filter(([_, v]) => v !== ""))
      );

      const response = await fetch(`http://localhost:8000/api/super_admin/audit-logs/export?${queryParams}`, {
        headers: { "Authorization": `Bearer ${token}` }
      });

      if (!response.ok) {
        throw new Error("Failed to export logs");
      }

      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `audit_logs_${new Date().toISOString().split('T')[0]}.csv`;
      a.click();
      window.URL.revokeObjectURL(url);
    } catch (err) {
      setError(err.message);
    }
  };

  const handleFilterChange = (key, value) => {
    setFilters(prev => ({ ...prev, [key]: value }));
    setPagination(prev => ({ ...prev, page: 1 }));
  };

  const clearFilters = () => {
    setFilters({
      institution_id: "",
      user_id: "",
      action_type: "",
      entity_type: "",
      date_from: "",
      date_to: "",
      search: ""
    });
    setPagination(prev => ({ ...prev, page: 1 }));
  };

  const getActionColor = (action) => {
    const colors = {
      CREATE: "action-create",
      UPDATE: "action-update",
      DELETE: "action-delete",
      LOGIN: "action-login",
      CHECK_OUT: "action-checkout",
      CHECK_IN: "action-checkin",
      TRANSFER: "action-transfer",
      RETIRE: "action-retire",
      PASSWORD_RESET: "action-reset",
      REVOKE_ADMIN: "action-revoke"
    };
    return colors[action] || "action-default";
  };

  return (
    <div className="reports-audit-container">
      {/* Header */}
      <div className="page-header">
        <div>
          <h1 className="page-title">
            <BarChart3 size={32} />
            Reports & Audit Center
          </h1>
          <p className="page-subtitle">System analytics and activity monitoring</p>
        </div>
      </div>

      {/* Section Toggle */}
      <div className="section-toggle">
        <button
          className={`toggle-btn ${activeSection === "analytics" ? "active" : ""}`}
          onClick={() => setActiveSection("analytics")}
        >
          <TrendingUp size={20} />
          Analytics & Reports
        </button>
        <button
          className={`toggle-btn ${activeSection === "audit" ? "active" : ""}`}
          onClick={() => setActiveSection("audit")}
        >
          <FileText size={20} />
          Audit Logs
        </button>
      </div>

      {error && (
        <div className="error-banner">
          <AlertCircle size={20} />
          {error}
          <button onClick={() => setError("")} className="close-error">×</button>
        </div>
      )}

      {/* Analytics Section */}
      {activeSection === "analytics" && (
        <div className="analytics-section">
          <div className="section-header">
            <h2>System Analytics & Intelligence</h2>
            <button onClick={exportAnalyticsReport} className="export-pdf-btn">
              <Download size={18} />
              Export PDF Report
            </button>
          </div>

          {loading ? (
            <div className="loading-container">
              <div className="spinner"></div>
              <p>Loading analytics...</p>
            </div>
          ) : (
            <>
              {/* Summary Cards */}
              <div className="summary-grid">
                <div className="summary-card card-blue">
                  <div className="card-icon">
                    <Activity size={32} />
                  </div>
                  <div className="card-content">
                    <p className="card-label">Total Assets</p>
                    <p className="card-value">{analyticsData.totalAssets}</p>
                  </div>
                </div>

                <div className="summary-card card-green">
                  <div className="card-icon">
                    <Activity size={32} />
                  </div>
                  <div className="card-content">
                    <p className="card-label">Active Assets</p>
                    <p className="card-value">{analyticsData.activeAssets}</p>
                  </div>
                </div>

                <div className="summary-card card-orange">
                  <div className="card-icon">
                    <AlertCircle size={32} />
                  </div>
                  <div className="card-content">
                    <p className="card-label">Retired Assets</p>
                    <p className="card-value">{analyticsData.retiredAssets}</p>
                  </div>
                </div>

                <div className="summary-card card-purple">
                  <div className="card-icon">
                    <TrendingUp size={32} />
                  </div>
                  <div className="card-content">
                    <p className="card-label">Retirement Rate</p>
                    <p className="card-value">
                      {((analyticsData.retiredAssets / analyticsData.totalAssets) * 100).toFixed(1)}%
                    </p>
                  </div>
                </div>
              </div>

              {/* Charts Grid */}
              <div className="charts-grid">
                {/* Asset Growth Over Time */}
                <div className="chart-card full-width">
                  <h3 className="chart-title">
                    <TrendingUp size={20} />
                    Asset Growth Over Time
                  </h3>
                  <ResponsiveContainer width="100%" height={300}>
                    <LineChart data={analyticsData.assetGrowth}>
                      <CartesianGrid strokeDasharray="3 3" />
                      <XAxis dataKey="month" />
                      <YAxis />
                      <Tooltip />
                      <Legend />
                      <Line 
                        type="monotone" 
                        dataKey="total" 
                        stroke="#667eea" 
                        strokeWidth={2}
                        name="Total Assets"
                      />
                      <Line 
                        type="monotone" 
                        dataKey="active" 
                        stroke="#10b981" 
                        strokeWidth={2}
                        name="Active"
                      />
                      <Line 
                        type="monotone" 
                        dataKey="retired" 
                        stroke="#ef4444" 
                        strokeWidth={2}
                        name="Retired"
                      />
                    </LineChart>
                  </ResponsiveContainer>
                </div>

                {/* Institution Comparison */}
                <div className="chart-card">
                  <h3 className="chart-title">
                    <Building2 size={20} />
                    Assets by Institution
                  </h3>
                  <ResponsiveContainer width="100%" height={300}>
                    <BarChart data={analyticsData.institutionComparison}>
                      <CartesianGrid strokeDasharray="3 3" />
                      <XAxis dataKey="name" />
                      <YAxis />
                      <Tooltip />
                      <Legend />
                      <Bar dataKey="total_assets" fill="#667eea" name="Total" />
                      <Bar dataKey="active_assets" fill="#10b981" name="Active" />
                      <Bar dataKey="retired_assets" fill="#ef4444" name="Retired" />
                    </BarChart>
                  </ResponsiveContainer>
                </div>

                {/* Retirement Stats */}
                <div className="chart-card">
                  <h3 className="chart-title">
                    <PieChartIcon size={20} />
                    Asset Status Distribution
                  </h3>
                  <ResponsiveContainer width="100%" height={300}>
                    <PieChart>
                      <Pie
                        data={[
                          { name: 'Available', value: analyticsData.retirementStats.available || 0 },
                          { name: 'On Loan', value: analyticsData.retirementStats.on_loan || 0 },
                          { name: 'Maintenance', value: analyticsData.retirementStats.maintenance || 0 },
                          { name: 'Retired', value: analyticsData.retirementStats.retired || 0 }
                        ]}
                        cx="50%"
                        cy="50%"
                        labelLine={false}
                        label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(0)}%`}
                        outerRadius={100}
                        fill="#8884d8"
                        dataKey="value"
                      >
                        {COLORS.map((color, index) => (
                          <Cell key={`cell-${index}`} fill={color} />
                        ))}
                      </Pie>
                      <Tooltip />
                    </PieChart>
                  </ResponsiveContainer>
                </div>
              </div>

              {/* Admin Activity Ranking */}
              <div className="activity-ranking">
                <h3 className="section-title">
                  <Award size={20} />
                  Top Admin Activity Ranking
                </h3>
                <div className="ranking-table">
                  <table>
                    <thead>
                      <tr>
                        <th>Rank</th>
                        <th>Admin</th>
                        <th>Institution</th>
                        <th>Total Actions</th>
                        <th>Last Activity</th>
                      </tr>
                    </thead>
                    <tbody>
                      {analyticsData.adminActivity && analyticsData.adminActivity.map((admin, index) => (
                        <tr key={admin.user_id}>
                          <td className="rank-cell">
                            <span className={`rank-badge rank-${index + 1}`}>
                              #{index + 1}
                            </span>
                          </td>
                          <td className="admin-cell">
                            <div className="admin-info">
                              <span className="admin-name">{admin.username}</span>
                              <span className="admin-email">{admin.email}</span>
                            </div>
                          </td>
                          <td>{admin.institution_name}</td>
                          <td className="actions-cell">{admin.action_count}</td>
                          <td>{new Date(admin.last_action).toLocaleDateString()}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            </>
          )}
        </div>
      )}

      {/* Audit Logs Section */}
      {activeSection === "audit" && (
        <div className="audit-section">
          <div className="section-header">
            <h2>Audit & Activity Logs</h2>
            <button onClick={exportAuditLogs} className="export-csv-btn">
              <Download size={18} />
              Export CSV
            </button>
          </div>

          {/* Filters */}
          <div className="filters-panel">
            <div className="filters-header">
              <Filter size={20} />
              <h3>Filters</h3>
              <button onClick={clearFilters} className="clear-btn">Clear All</button>
            </div>

            <div className="filters-grid">
              <div className="filter-group">
                <Building2 size={18} />
                <select
                  value={filters.institution_id}
                  onChange={(e) => handleFilterChange("institution_id", e.target.value)}
                  className="filter-select"
                >
                  <option value="">All Institutions</option>
                  {institutions.map((inst) => (
                    <option key={inst.id} value={inst.id}>{inst.name}</option>
                  ))}
                </select>
              </div>

              <div className="filter-group">
                <User size={18} />
                <select
                  value={filters.user_id}
                  onChange={(e) => handleFilterChange("user_id", e.target.value)}
                  className="filter-select"
                >
                  <option value="">All Users</option>
                  {users.map((user) => (
                    <option key={user.id} value={user.id}>
                      {user.username} - {user.institution_name}
                    </option>
                  ))}
                </select>
              </div>

              <div className="filter-group">
                <Activity size={18} />
                <select
                  value={filters.action_type}
                  onChange={(e) => handleFilterChange("action_type", e.target.value)}
                  className="filter-select"
                >
                  <option value="">All Actions</option>
                  {actionTypes.map((action) => (
                    <option key={action} value={action}>{action}</option>
                  ))}
                </select>
              </div>

              <div className="filter-group">
                <FileText size={18} />
                <select
                  value={filters.entity_type}
                  onChange={(e) => handleFilterChange("entity_type", e.target.value)}
                  className="filter-select"
                >
                  <option value="">All Entities</option>
                  {entityTypes.map((entity) => (
                    <option key={entity} value={entity}>{entity}</option>
                  ))}
                </select>
              </div>

              <div className="filter-group">
                <Calendar size={18} />
                <input
                  type="date"
                  value={filters.date_from}
                  onChange={(e) => handleFilterChange("date_from", e.target.value)}
                  className="filter-input"
                  placeholder="From Date"
                />
              </div>

              <div className="filter-group">
                <Calendar size={18} />
                <input
                  type="date"
                  value={filters.date_to}
                  onChange={(e) => handleFilterChange("date_to", e.target.value)}
                  className="filter-input"
                  placeholder="To Date"
                />
              </div>

              <div className="filter-group search-group">
                <Search size={18} />
                <input
                  type="text"
                  value={filters.search}
                  onChange={(e) => handleFilterChange("search", e.target.value)}
                  className="filter-input"
                  placeholder="Search logs..."
                />
              </div>
            </div>
          </div>

          {/* Logs Table */}
          <div className="logs-panel">
            <div className="logs-info">
              <p>Showing {logs.length} of {pagination.total} logs (Immutable Records)</p>
            </div>

            {loading ? (
              <div className="loading-container">
                <div className="spinner"></div>
                <p>Loading audit logs...</p>
              </div>
            ) : (
              <>
                <div className="table-wrapper">
                  <table className="logs-table">
                    <thead>
                      <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Institution</th>
                        <th>Action</th>
                        <th>Entity</th>
                        <th>Entity ID</th>
                        <th>Details</th>
                      </tr>
                    </thead>
                    <tbody>
                      {logs.length > 0 ? (
                        logs.map((log) => (
                          <tr key={log.id}>
                            <td className="timestamp-col">
                              {new Date(log.created_at).toLocaleString()}
                            </td>
                            <td>{log.username || "System"}</td>
                            <td>{log.institution_name || "N/A"}</td>
                            <td>
                              <span className={`action-tag ${getActionColor(log.action)}`}>
                                {log.action}
                              </span>
                            </td>
                            <td>{log.entity_type}</td>
                            <td>{log.entity_id || "-"}</td>
                            <td className="details-col">
                              {log.details && (
                                <details>
                                  <summary>View</summary>
                                  <pre>{JSON.stringify(log.details, null, 2)}</pre>
                                </details>
                              )}
                            </td>
                          </tr>
                        ))
                      ) : (
                        <tr>
                          <td colSpan="7" className="no-data">
                            No logs found
                          </td>
                        </tr>
                      )}
                    </tbody>
                  </table>
                </div>

                {/* Pagination */}
                {pagination.total > pagination.limit && (
                  <div className="pagination-controls">
                    <button
                      onClick={() => setPagination(prev => ({ ...prev, page: Math.max(1, prev.page - 1) }))}
                      disabled={pagination.page === 1}
                      className="page-btn"
                    >
                      Previous
                    </button>
                    <span className="page-info">
                      Page {pagination.page} of {Math.ceil(pagination.total / pagination.limit)}
                    </span>
                    <button
                      onClick={() => setPagination(prev => ({ ...prev, page: prev.page + 1 }))}
                      disabled={pagination.page >= Math.ceil(pagination.total / pagination.limit)}
                      className="page-btn"
                    >
                      Next
                    </button>
                  </div>
                )}
              </>
            )}
          </div>
        </div>
      )}
    </div>
  );
}