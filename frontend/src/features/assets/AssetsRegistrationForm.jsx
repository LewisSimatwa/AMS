import { useState } from "react";

export default function AssetRegistrationForm({ onAssetAdded }) {
  const [form, setForm] = useState({
    name: "",
    asset_code: "",
    category: "",
    purchase_date: "",
    purchase_cost: "",
    location: "",
    assigned_to: "",
    status: "available",
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");

  const token = localStorage.getItem("token");
  const institutionId = localStorage.getItem("institutionId");

  function handleChange(e) {
    setForm({ ...form, [e.target.name]: e.target.value });
    setError("");
    setSuccess("");
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setLoading(true);
    setError("");
    setSuccess("");

    try {
      const headers = {
        "Content-Type": "application/json",
        Authorization: `Bearer ${token}`,
        "X-Institution-ID": institutionId,
      };

      const response = await fetch("http://localhost:8000/api/assets", {
        method: "POST",
        headers,
        body: JSON.stringify({
          ...form,
          institution_id: institutionId,
          purchase_cost: parseFloat(form.purchase_cost) || 0,
        }),
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.detail || "Failed to register asset");
      }

      const newAsset = await response.json();
      setSuccess("Asset registered successfully!");
      
      // Reset form
      setForm({
        name: "",
        asset_code: "",
        category: "",
        purchase_date: "",
        purchase_cost: "",
        location: "",
        assigned_to: "",
        status: "available",
      });

      // Notify parent component
      if (onAssetAdded) {
        onAssetAdded(newAsset);
      }
    } catch (err) {
      console.error("Registration error:", err);
      setError(err.message || "Failed to register asset");
    } finally {
      setLoading(false);
    }
  }

  return (
    <form className="asset-form" onSubmit={handleSubmit}>
      <h2>Register New Asset</h2>

      {error && <div className="error-message">{error}</div>}
      {success && <div className="success-message">{success}</div>}

      <input
        name="name"
        placeholder="Asset name *"
        value={form.name}
        onChange={handleChange}
        required
      />

      <input
        name="asset_code"
        placeholder="Asset code / Tag (QR / Barcode / NFC) *"
        value={form.asset_code}
        onChange={handleChange}
        required
      />

      <input
        name="category"
        placeholder="Category (e.g., Electronics, Furniture)"
        value={form.category}
        onChange={handleChange}
      />

      <input
        name="purchase_date"
        type="date"
        placeholder="Purchase date"
        value={form.purchase_date}
        onChange={handleChange}
      />

      <input
        name="purchase_cost"
        type="number"
        step="0.01"
        placeholder="Purchase cost"
        value={form.purchase_cost}
        onChange={handleChange}
      />

      <input
        name="location"
        placeholder="Location"
        value={form.location}
        onChange={handleChange}
      />

      <input
        name="assigned_to"
        placeholder="Assigned to (optional)"
        value={form.assigned_to}
        onChange={handleChange}
      />

      <select name="status" value={form.status} onChange={handleChange}>
        <option value="available">Available</option>
        <option value="on_loan">On Loan</option>
        <option value="maintenance">Maintenance</option>
        <option value="retired">Retired</option>
      </select>

      <button type="submit" disabled={loading}>
        {loading ? "Registering..." : "Add Asset"}
      </button>
    </form>
  );
}