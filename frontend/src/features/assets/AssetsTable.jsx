import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";

export default function AssetTable({ onView, onCheckInOut, onMaintenance, refreshTrigger }) {
  const navigate = useNavigate();
  const [assets, setAssets] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [searchTerm, setSearchTerm] = useState("");

  const token = localStorage.getItem("token");
  const institutionId = localStorage.getItem("institutionId");

  useEffect(() => {
    if (!token || !institutionId) {
      navigate("/login");
      return;
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

  const filteredAssets = assets.filter((asset) =>
    asset.name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
    asset.asset_code?.toLowerCase().includes(searchTerm.toLowerCase()) ||
    asset.category?.toLowerCase().includes(searchTerm.toLowerCase())
  );

  if (loading) {
    return <div className="loading-spinner">Loading assets...</div>;
  }

  if (error) {
    return (
      <div className="error-message">
        <p>{error}</p>
        <button onClick={fetchAssets}>Retry</button>
      </div>
    );
  }

  if (assets.length === 0) {
    return <p>No assets registered yet.</p>;
  }

  return (
    <div className="asset-table">
      <div className="table-header">
        <h2>Registered Assets ({assets.length})</h2>
        <input
          type="text"
          placeholder="Search assets..."
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          className="search-input"
        />
      </div>

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
          {filteredAssets.map((asset) => (
            <tr key={asset.id}>
              <td>{asset.asset_code}</td>
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
                <button onClick={() => onView(asset)}>View</button>
                <button onClick={() => onCheckInOut(asset)}>Move</button>
                <button onClick={() => onMaintenance(asset)}>Status</button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>

      {filteredAssets.length === 0 && searchTerm && (
        <p className="no-results">No assets found matching "{searchTerm}"</p>
      )}
    </div>
  );
}