import { useState, useEffect } from "react";
import { 
  Building2, 
  Package, 
  Users, 
  AlertTriangle,
  TrendingUp,
  Activity
} from "lucide-react";
import "../../styles/SuperAdmin/Dashboard.css";

export default function SuperAdminDashboard() {
  const [loading, setLoading] = useState(true);
  const [stats, setStats] = useState({
    totalInstitutions: 0,
    activeInstitutions: 0,
    totalAssets: 0,
    activeAssets: 0,
    retiredAssets: 0,
    totalUsers: 0,
    assetsPerInstitution: []
  });
  const [recentActions, setRecentActions] = useState([]);
  const [error, setError] = useState("");

  useEffect(() => {
    fetchDashboardData();
  }, []);

  const fetchDashboardData = async () => {
    setLoading(true);
    setError("");

    try {
      const token = localStorage.getItem("token");
      
      if (!token) {
        setError("No authentication token found");
        setLoading(false);
        return;
      }

      const headers = {
        "Authorization": `Bearer ${token}`,
        "Content-Type": "application/json"
      };

      console.log("Fetching super admin stats...");

      // Fetch stats
      const statsResponse = await fetch("http://localhost:8000/api/super_admin/stats", {
        method: "GET",
        headers: headers
      });

      console.log("Stats response status:", statsResponse.status);

      if (!statsResponse.ok) {
        const errorText = await statsResponse.text();
        console.error("Stats error:", errorText);
        throw new Error(`Failed to fetch stats: ${statsResponse.status}`);
      }

      const statsData = await statsResponse.json();
      console.log("Stats data:", statsData);
      
      // Fetch recent high-risk actions
      const actionsResponse = await fetch("http://localhost:8000/api/super_admin/recent-actions", {
        method: "GET",
        headers: headers
      });

      console.log("Actions response status:", actionsResponse.status);

      let actionsData = { actions: [] };
      if (actionsResponse.ok) {
        actionsData = await actionsResponse.json();
        console.log("Actions data:", actionsData);
      } else {
        console.warn("Failed to fetch recent actions");
      }
      
      setStats(statsData);
      setRecentActions(actionsData.actions || []);
    } catch (error) {
      console.error("Failed to fetch dashboard data:", error);
      setError(error.message || "Failed to load dashboard data");
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-full">
        <div className="loading-spinner"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="p-8">
        <div className="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
          <p className="text-red-800">{error}</p>
          <button 
            onClick={fetchDashboardData}
            className="mt-2 px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
          >
            Retry
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="p-8 custom-scrollbar">
      {/* Header */}
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900">System Overview</h1>
        <p className="text-gray-600 mt-1">Monitor system-wide activity and health</p>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 stats-grid">
        {/* Total Institutions */}
        <div className="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500 stats-card hover-lift">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-600 mb-1">Total Institutions</p>
              <p className="text-3xl font-bold text-gray-900 stat-number">{stats.totalInstitutions}</p>
              <div className="flex items-center mt-2">
                <span className="status-dot status-dot-green"></span>
                <p className="text-xs text-green-600">{stats.activeInstitutions} active</p>
              </div>
            </div>
            <div className="icon-container bg-blue-100">
              <Building2 className="text-blue-600" size={24} />
            </div>
          </div>
        </div>

        {/* Total Assets */}
        <div className="bg-white rounded-lg shadow p-6 border-l-4 border-green-500 stats-card hover-lift">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-600 mb-1">Total Assets</p>
              <p className="text-3xl font-bold text-gray-900 stat-number">{stats.totalAssets}</p>
              <div className="flex items-center mt-2">
                <span className="status-dot status-dot-green"></span>
                <p className="text-xs text-green-600">{stats.activeAssets} active</p>
              </div>
            </div>
            <div className="icon-container bg-green-100">
              <Package className="text-green-600" size={24} />
            </div>
          </div>
        </div>

        {/* Total Users */}
        <div className="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500 stats-card hover-lift">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-600 mb-1">Total Users</p>
              <p className="text-3xl font-bold text-gray-900 stat-number">{stats.totalUsers}</p>
              <p className="text-xs text-gray-500 mt-2">Across all institutions</p>
            </div>
            <div className="icon-container bg-purple-100">
              <Users className="text-purple-600" size={24} />
            </div>
          </div>
        </div>

        {/* Retired Assets */}
        <div className="bg-white rounded-lg shadow p-6 border-l-4 border-orange-500 stats-card hover-lift">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-600 mb-1">Retired Assets</p>
              <p className="text-3xl font-bold text-gray-900 stat-number">{stats.retiredAssets}</p>
              <p className="text-xs text-gray-500 mt-2">Out of service</p>
            </div>
            <div className="icon-container bg-orange-100">
              <Activity className="text-orange-600" size={24} />
            </div>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 chart-grid">
        {/* Assets Per Institution */}
        <div className="bg-white rounded-lg shadow p-6 card-shadow-md">
          <div className="flex items-center mb-4">
            <TrendingUp className="text-blue-600 mr-2" size={20} />
            <h2 className="text-lg font-semibold text-gray-900">Assets Per Institution</h2>
          </div>
          <div className="space-y-3 custom-scrollbar" style={{ maxHeight: "400px", overflowY: "auto" }}>
            {stats.assetsPerInstitution && stats.assetsPerInstitution.length > 0 ? (
              stats.assetsPerInstitution.map((inst, index) => (
                <div key={index} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg activity-item institution-card">
                  <div className="flex items-center space-x-3">
                    <div className="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                      <Building2 className="text-blue-600" size={18} />
                    </div>
                    <div>
                      <p className="font-medium text-gray-900">{inst.name}</p>
                      <p className="text-xs text-gray-500">{inst.asset_count} assets</p>
                    </div>
                  </div>
                  <div className="text-right">
                    <span className="badge badge-green">{inst.active_assets} active</span>
                    <p className="text-xs text-gray-500 mt-1">{inst.retired_assets} retired</p>
                  </div>
                </div>
              ))
            ) : (
              <div className="empty-state">
                <Package className="empty-state-icon" />
                <p>No data available</p>
              </div>
            )}
          </div>
        </div>

        {/* Recent High-Risk Actions */}
        <div className="bg-white rounded-lg shadow p-6 card-shadow-md">
          <div className="flex items-center mb-4">
            <AlertTriangle className="text-orange-600 mr-2" size={20} />
            <h2 className="text-lg font-semibold text-gray-900">Recent High-Risk Actions</h2>
          </div>
          <div className="space-y-3 custom-scrollbar" style={{ maxHeight: "400px", overflowY: "auto" }}>
            {recentActions.length > 0 ? (
              recentActions.map((action, index) => (
                <div key={index} className="p-3 border-l-4 border-orange-400 bg-orange-50 rounded activity-item">
                  <div className="flex items-start justify-between">
                    <div className="flex-1">
                      <p className="font-medium text-gray-900">{action.action_type || action.action}</p>
                      <p className="text-sm text-gray-600 mt-1">{action.description || 'No description'}</p>
                      <p className="text-xs text-gray-500 mt-2">
                        {action.user_name ? `By ${action.user_name}` : 'Unknown user'}
                        {action.institution_name ? ` at ${action.institution_name}` : ''}
                      </p>
                    </div>
                    <span className="badge badge-yellow whitespace-nowrap ml-2">
                      {action.timestamp ? new Date(action.timestamp).toLocaleDateString() : 'Unknown date'}
                    </span>
                  </div>
                </div>
              ))
            ) : (
              <div className="empty-state">
                <AlertTriangle className="empty-state-icon" />
                <p>No recent actions</p>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}