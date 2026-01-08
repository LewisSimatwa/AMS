import { useState } from "react";
import "../../styles/DeleteConfirmationModal.css";

export default function DeleteConfirmationModal({ asset, onClose, onConfirm }) {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [confirmText, setConfirmText] = useState("");

  const token = localStorage.getItem("token");
  const institutionId = localStorage.getItem("institutionId");

  async function handleDelete() {
    // Validate confirmation text
    if (confirmText !== asset.asset_code) {
      setError("Asset code does not match");
      return;
    }

    // Check authentication
    if (!token || !institutionId) {
      setError("Missing authentication credentials. Please login again.");
      return;
    }

    setLoading(true);
    setError("");

    try {
      console.log("=== DELETE REQUEST START ===");
      console.log("Asset ID:", asset.id);
      console.log("Asset Code:", asset.asset_code);
      console.log("URL:", `http://localhost:8000/api/delete_asset.php?id=${asset.id}`);
      console.log("Token exists:", !!token);
      console.log("Institution ID:", institutionId);
      
      const response = await fetch(
        `http://localhost:8000/api/delete_asset.php?id=${asset.id}`,
        {
          method: "POST",
          headers: {
            "Authorization": `Bearer ${token}`,
            "X-Institution-ID": institutionId,
            "Content-Type": "application/json"
          },
          body: JSON.stringify({
            _method: "DELETE",
            asset_id: asset.id
          })
        }
      );

      console.log("Response status:", response.status);
      console.log("Response ok:", response.ok);
      console.log("Response headers:", {
        contentType: response.headers.get('content-type'),
        contentLength: response.headers.get('content-length')
      });
      
      // Get response as text first to see what we're getting
      const rawText = await response.text();
      console.log("Raw response text:", rawText);
      console.log("Response length:", rawText.length);

      // Check if response is empty
      if (!rawText || rawText.trim().length === 0) {
        console.error("Empty response received");
        throw new Error("Server returned empty response. Check PHP error log.");
      }

      // Try to parse as JSON
      let data;
      try {
        data = JSON.parse(rawText);
        console.log("Parsed JSON data:", data);
      } catch (parseError) {
        console.error("JSON parse error:", parseError);
        console.error("Response was:", rawText.substring(0, 500));
        throw new Error(`Server returned invalid JSON. Response: ${rawText.substring(0, 200)}`);
      }

      // Check for errors in response
      if (!response.ok) {
        const errorMsg = data.error || `Server error: ${response.status}`;
        console.error("Server error:", errorMsg);
        throw new Error(errorMsg);
      }

      // Check if deletion was successful
      if (!data.success) {
        const errorMsg = data.error || "Failed to delete asset";
        console.error("Deletion failed:", errorMsg);
        throw new Error(errorMsg);
      }

      console.log("=== DELETE SUCCESS ===");
      console.log("Deleted asset:", data.deleted_asset);
      
      // Show success message
      alert(`✓ Asset "${asset.name}" (${asset.asset_code}) deleted successfully`);
      
      // Close modal and refresh list
      onConfirm();
      
    } catch (err) {
      console.error("=== DELETE ERROR ===");
      console.error("Error type:", err.name);
      console.error("Error message:", err.message);
      console.error("Full error:", err);
      
      setError(err.message || "Failed to delete asset. Check console for details.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="modal-overlay" onClick={onClose}>
      <div className="modal-container delete-modal" onClick={(e) => e.stopPropagation()}>
        <div className="modal-header">
          <div className="warning-icon">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
          </div>
          <h2>Delete Asset</h2>
          <button 
            className="modal-close" 
            onClick={onClose}
            type="button"
            disabled={loading}
          >
            ×
          </button>
        </div>

        <div className="modal-body">
          <div className="warning-message">
            <p><strong>Warning:</strong> This action cannot be undone!</p>
            <p>You are about to permanently delete this asset:</p>
          </div>

          <div className="asset-info">
            <div className="info-row">
              <span className="label">Asset ID:</span>
              <span className="value">{asset.id}</span>
            </div>
            <div className="info-row">
              <span className="label">Asset Code:</span>
              <span className="value">{asset.asset_code}</span>
            </div>
            <div className="info-row">
              <span className="label">Name:</span>
              <span className="value">{asset.name}</span>
            </div>
            <div className="info-row">
              <span className="label">Category:</span>
              <span className="value">{asset.category || "—"}</span>
            </div>
            <div className="info-row">
              <span className="label">Status:</span>
              <span className={`status-badge ${asset.status}`}>
                {asset.status?.replace("_", " ")}
              </span>
            </div>
          </div>

          {error && (
            <div className="error-message">
              <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd"/>
              </svg>
              <div>
                <strong>Error:</strong> {error}
                <br />
                <small>Check browser console (F12) for details</small>
              </div>
            </div>
          )}

          <div className="confirmation-input">
            <label htmlFor="confirmText">
              To confirm deletion, type the asset code <strong>{asset.asset_code}</strong>
            </label>
            <input
              id="confirmText"
              type="text"
              placeholder="Enter asset code"
              value={confirmText}
              onChange={(e) => {
                setConfirmText(e.target.value);
                setError("");
              }}
              disabled={loading}
              autoComplete="off"
            />
          </div>
        </div>

        <div className="modal-actions">
          <button
            type="button"
            onClick={onClose}
            disabled={loading}
            className="btn-cancel"
          >
            Cancel
          </button>
          <button
            type="button"
            onClick={handleDelete}
            disabled={loading || confirmText !== asset.asset_code}
            className="btn-delete-confirm"
          >
            {loading ? (
              <>
                <span className="spinner"></span>
                Deleting...
              </>
            ) : (
              <>
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                  <path fillRule="evenodd" d="M5 3V1h6v2h4v2H1V3h4zm2 0h2V2H7v1zm7 3v9a1 1 0 01-1 1H3a1 1 0 01-1-1V6h12z"/>
                </svg>
                Delete Asset
              </>
            )}
          </button>
        </div>
      </div>
    </div>
  );
}