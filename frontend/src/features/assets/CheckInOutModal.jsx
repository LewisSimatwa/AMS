import { useState } from "react";

export default function CheckInOutModal({ asset, onClose, onSuccess }) {
  const [assignedTo, setAssignedTo] = useState(asset.assigned_to || "");
  const [location, setLocation] = useState(asset.location || "");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const token = localStorage.getItem("token");
  const institutionId = localStorage.getItem("institutionId");

  async function handleSubmit(e) {
    e.preventDefault();
    setLoading(true);
    setError("");

    try {
      const headers = {
        "Content-Type": "application/json",
        Authorization: `Bearer ${token}`,
        "X-Institution-ID": institutionId,
      };

      const response = await fetch(
        `http://localhost:8000/api/assets/${asset.id}`,
        {
          method: "PUT",
          headers,
          body: JSON.stringify({
            ...asset,
            assigned_to: assignedTo,
            location: location,
          }),
        }
      );

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.detail || "Failed to update asset");
      }

      const updatedAsset = await response.json();

      // Log the movement in audit logs
      try {
        await fetch("http://localhost:8000/api/audit/log", {
          method: "POST",
          headers,
          body: JSON.stringify({
            institution_id: institutionId,
            action: "Asset Movement",
            details: `${asset.name} moved to ${assignedTo || "Unassigned"} at ${location || "Unknown location"}`,
            asset_id: asset.id,
          }),
        });
      } catch (auditErr) {
        console.warn("Failed to log audit:", auditErr);
      }

      if (onSuccess) {
        onSuccess(updatedAsset);
      }
      onClose();
    } catch (err) {
      console.error("Update error:", err);
      setError(err.message || "Failed to update asset");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="modal" onClick={onClose}>
      <div className="modal-content" onClick={(e) => e.stopPropagation()}>
        <h2>Asset Movement</h2>
        <p className="modal-subtitle">
          Moving: <strong>{asset.name}</strong> ({asset.asset_code})
        </p>

        {error && <div className="error-message">{error}</div>}

        <form onSubmit={handleSubmit}>
          <input
            value={assignedTo}
            onChange={(e) => setAssignedTo(e.target.value)}
            placeholder="Assigned to (person/department)"
          />
          <input
            value={location}
            onChange={(e) => setLocation(e.target.value)}
            placeholder="New location"
          />

          <div className="modal-actions">
            <button type="submit" disabled={loading}>
              {loading ? "Saving..." : "Save Movement"}
            </button>
            <button type="button" onClick={onClose} disabled={loading}>
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}