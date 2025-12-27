// ./features/maintenance/MaintenancePage.jsx
import { useEffect, useState } from "react";
import "../../styles/Maintenance.css";

export default function MaintenancePage() {
  const [assets, setAssets] = useState([]);
  const [users, setUsers] = useState([]); // new state for users
  const [maintenanceRecords, setMaintenanceRecords] = useState([]);
  const [predictiveData, setPredictiveData] = useState([]);
  const [loading, setLoading] = useState(true);
  const [newMaintenance, setNewMaintenance] = useState({
    asset_id: "",
    maintenance_type: "preventive",
    description: "",
    assigned_to: "",
    start_date: "",
  });
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");

  const token = localStorage.getItem("token");
  const institutionId = localStorage.getItem("institutionId");

  const headers = {
    "Content-Type": "application/json",
    Authorization: `Bearer ${token}`,
    "X-Institution-ID": institutionId,
  };

  useEffect(() => {
    async function fetchData() {
      try {
        const [assetsRes, maintenanceRes, predictiveRes, usersRes] = await Promise.all([
          fetch(`http://localhost:8000/api/assets?institution_id=${institutionId}`, { headers }),
          fetch(`http://localhost:8000/api/maintenance?institution_id=${institutionId}`, { headers }),
          fetch(`http://localhost:8000/api/predictive?institution_id=${institutionId}`, { headers }),
          fetch(`http://localhost:8000/api/users?institution_id=${institutionId}`, { headers }), // fetch users
        ]);

        const [assetsData, maintenanceData, predictiveData, usersData] = await Promise.all([
          assetsRes.json(),
          maintenanceRes.json(),
          predictiveRes.json(),
          usersRes.json(),
        ]);

        setAssets(assetsData.assets || []);
        setMaintenanceRecords(maintenanceData || []);
        setPredictiveData(predictiveData || []);
        setUsers(usersData.users || []); // set users
      } catch (err) {
        console.error("Error fetching maintenance data:", err);
      } finally {
        setLoading(false);
      }
    }
    fetchData();
  }, [institutionId]);

  const handleScheduleSubmit = async (e) => {
    e.preventDefault();
    setError("");
    setSuccess("");
    if (!newMaintenance.asset_id || !newMaintenance.assigned_to || !newMaintenance.start_date) {
      setError("Please fill all required fields.");
      return;
    }
    try {
      const res = await fetch(`http://localhost:8000/api/maintenance`, {
        method: "POST",
        headers,
        body: JSON.stringify({ ...newMaintenance, institution_id: institutionId }),
      });
      if (!res.ok) throw new Error("Failed to schedule maintenance");
      const data = await res.json();
      setMaintenanceRecords([...maintenanceRecords, data]);
      setSuccess("Maintenance scheduled successfully!");
      setNewMaintenance({
        asset_id: "",
        maintenance_type: "preventive",
        description: "",
        assigned_to: "",
        start_date: "",
      });
    } catch (err) {
      setError(err.message);
    }
  };

  if (loading) return <p>Loading maintenance data...</p>;

  return (
    <div style={{ padding: "20px", fontFamily: "sans-serif" }}>
      <h1>🛠 Maintenance Module</h1>

      <section style={{ marginTop: "30px" }}>
        <h2>Schedule Maintenance</h2>
        {error && <p style={{ color: "red" }}>{error}</p>}
        {success && <p style={{ color: "green" }}>{success}</p>}
        <form onSubmit={handleScheduleSubmit} style={{ display: "flex", flexDirection: "column", gap: "10px", maxWidth: "400px" }}>
          
          <label>
            Asset:
            <select
              value={newMaintenance.asset_id}
              onChange={(e) => setNewMaintenance({ ...newMaintenance, asset_id: e.target.value })}
            >
              <option value="">Select asset</option>
              {assets.map((asset) => (
                <option key={asset.id} value={asset.id}>
                  {asset.name} ({asset.asset_code})
                </option>
              ))}
            </select>
          </label>

          <label>
            Maintenance Type:
            <select
              value={newMaintenance.maintenance_type}
              onChange={(e) => setNewMaintenance({ ...newMaintenance, maintenance_type: e.target.value })}
            >
              <option value="preventive">Preventive</option>
              <option value="corrective">Corrective</option>
            </select>
          </label>

          <label>
            Assigned To (Username):
            <select
              value={newMaintenance.assigned_to}
              onChange={(e) => setNewMaintenance({ ...newMaintenance, assigned_to: e.target.value })}
            >
              <option value="">Select user</option>
              {users.map((user) => (
                <option key={user.id} value={user.id}>
                  {user.username}
                </option>
              ))}
            </select>
          </label>

          <label>
            Start Date:
            <input
              type="date"
              value={newMaintenance.start_date}
              onChange={(e) => setNewMaintenance({ ...newMaintenance, start_date: e.target.value })}
            />
          </label>

          <label>
            Description:
            <textarea
              value={newMaintenance.description}
              onChange={(e) => setNewMaintenance({ ...newMaintenance, description: e.target.value })}
              rows={3}
            />
          </label>

          <button type="submit">Schedule Maintenance</button>
        </form>
      </section>

      {/* ... Maintenance Records, Predictive Maintenance, Summary remain unchanged ... */}

    </div>
  );
}
