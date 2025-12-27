import { useState } from "react";
//import "../../styles/Maintenance.css";

export default function MaintenanceForm({ asset, updateAsset, onClose }) {
  const [status, setStatus] = useState(asset.status);

  function handleSubmit(e) {
    e.preventDefault();

    updateAsset({
      ...asset,
      status,
      history: [
        ...asset.history,
        `Status changed to ${status} on ${new Date().toLocaleDateString()}`
      ],
    });

    onClose();
  }

  return (
    <div className="modal">
      <form className="modal-content" onSubmit={handleSubmit}>
        <h2>Update Asset Status</h2>

        <select value={status} onChange={e => setStatus(e.target.value)}>
          <option value="Active">Active</option>
          <option value="Repair">Repair</option>
          <option value="Lost">Lost</option>
          <option value="Retired">Retired</option>
        </select>

        <div className="modal-actions">
          <button type="submit">Update</button>
          <button type="button" onClick={onClose}>Cancel</button>
        </div>
      </form>
    </div>
  );
}
