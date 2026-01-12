import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import "../../styles/AssetsTable.css";

export default function AssetTable({ onView, onRetire, refreshTrigger }) {
  const navigate = useNavigate();
  const [assets, setAssets] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [searchTerm, setSearchTerm] = useState("");
  const [filter, setFilter] = useState("all"); // all, active, retired
  const [userRole, setUserRole] = useState("");

  const token = localStorage.getItem("token");
  const institutionId = localStorage.getItem("institutionId");

  useEffect(() => {
    if (!token || !institutionId) {
      navigate("/login");
      return;
    }

    // Get user role from token (decode JWT)
    try {
      const payload = JSON.parse(atob(token.split('.')[1]));
      setUserRole(payload.role || "");
    } catch (e) {
      console.error("Failed to decode token:", e);
    }

    fetchAssets();
  }, [token, institutionId, refreshTrigger]);

  async function fetchAssets() {
    setLoading(true);
    setError("");

    try {
      const headers = {
        Authorization: `Bearer ${token}`,
        "X-Institution-ID": institutionId,
      };

      const response = await fetch(
        `http://localhost:8000/api/assets?institution_id=${institutionId}`,
        { headers }
      );

      if (!response.ok) {
        throw new Error("Failed to fetch assets");
      }

      const data = await response.json();
      setAssets(data.assets || []);
    } catch (err) {
      console.error("Fetch assets error:", err);
      setError(err.message || "Failed to load assets");
    } finally {
      setLoading(false);
    }
  }

  // Filter assets based on status and search term
  const filteredAssets = assets.filter((asset) => {
    const matchesFilter = 
      filter === "all" ? true :
      filter === "active" ? asset.status !== "retired" :
      filter === "retired" ? asset.status === "retired" :
      true;

    const matchesSearch = 
      searchTerm === "" ||
      asset.name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      asset.asset_code?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      asset.category?.toLowerCase().includes(searchTerm.toLowerCase());

    return matchesFilter && matchesSearch;
  });

  const activeCount = assets.filter(a => a.status !== "retired").length;
  const retiredCount = assets.filter(a => a.status === "retired").length;

  if (loading) {
    return (
      <div className="loading-container">
        <div className="spinner-large"></div>
        <p>Loading assets...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="error-container">
        <p>{error}</p>
        <button onClick={fetchAssets}>Retry</button>
      </div>
    );
  }

  if (assets.length === 0) {
    return (
      <div className="empty-state">
        <p>No assets registered yet.</p>
      </div>
    );
  }

  return (
    <div className="asset-table">
      <div className="table-header">
        <h2>Registered Assets ({assets.length})</h2>
        
        <div className="table-controls">
          <div className="search-box">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
              <path fillRule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clipRule="evenodd"/>
            </svg>
            <input
              type="text"
              placeholder="Search assets..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="search-input"
            />
          </div>

          <div className="filter-tabs">
            <button
              className={filter === "all" ? "active" : ""}
              onClick={() => setFilter("all")}
            >
              All ({assets.length})
            </button>
            <button
              className={filter === "active" ? "active" : ""}
              onClick={() => setFilter("active")}
            >
              Active ({activeCount})
            </button>
            <button
              className={filter === "retired" ? "active" : ""}
              onClick={() => setFilter("retired")}
            >
              Retired ({retiredCount})
            </button>
          </div>
        </div>
      </div>

      <div className="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Asset Code</th>
              <th>Name</th>
              <th>Category</th>
              <th>Location</th>
              <th>Assigned To</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>

          <tbody>
            {filteredAssets.map((asset) => {
              const isRetired = asset.status === "retired";
              
              return (
                <tr key={asset.id} className={isRetired ? "retired-row" : ""}>
                  <td>
                    <strong>{asset.asset_code}</strong>
                    {isRetired && (
                      <span className="retired-badge">RETIRED</span>
                    )}
                  </td>
                  <td>{asset.name}</td>
                  <td>{asset.category || "—"}</td>
                  <td>{asset.location || "—"}</td>
                  <td>{asset.assigned_to || "—"}</td>
                  <td>
                    <span className={`status-badge ${asset.status}`}>
                      {asset.status?.replace("_", " ")}
                    </span>
                  </td>
                  <td className="actions">
                    <button 
                      onClick={() => onView(asset)} 
                      className="btn-view"
                      title="View details"
                    >
                      <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M8 3.5a4.5 4.5 0 100 9 4.5 4.5 0 000-9zM0 8a8 8 0 1116 0A8 8 0 010 8zm8-3a3 3 0 100 6 3 3 0 000-6z"/>
                      </svg>
                      View
                    </button>

                    {userRole === "admin" && !isRetired && (
                      <button 
                        onClick={() => onRetire(asset)} 
                        className="btn-retire"
                        title="Retire this asset"
                      >
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                          <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                          <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                        </svg>
                        Retire
                      </button>
                    )}

                    {isRetired && (
                      <span className="retired-text">
                        {asset.date_retired 
                          ? `Retired: ${new Date(asset.date_retired).toLocaleDateString()}`
                          : "Retired"}
                      </span>
                    )}
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>

      {filteredAssets.length === 0 && searchTerm && (
        <p className="no-results">No assets found matching "{searchTerm}"</p>
      )}
    </div>
  );
}