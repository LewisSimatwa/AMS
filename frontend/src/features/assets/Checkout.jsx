import React, { useState, useEffect } from "react";
import "../../styles/Checkout.css";

export default function CheckoutModule() {
  const [assets, setAssets] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    const fetchAssets = async () => {
      setLoading(true);
      setError("");

      try {
        console.log("Fetching assets..."); // Debug
        
        const res = await fetch("/api/available.php", {
          method: "GET",
          credentials: "include",
          headers: {
            "Content-Type": "application/json",
          },
        });

        console.log("Response status:", res.status); // Debug
        
        if (!res.ok) {
          setError(`Server error: ${res.status} ${res.statusText}`);
          setLoading(false);
          return;
        }

        const data = await res.json();
        console.log("Received data:", data); // Debug

        if (data.error) {
          setError(data.error);
        } else if (Array.isArray(data)) {
          setAssets(data);
        } else {
          setError("Unexpected response from server");
        }
      } catch (err) {
        console.error("Fetch error:", err);
        setError(`Could not load assets: ${err.message}`);
      } finally {
        setLoading(false);
      }
    };

    fetchAssets();
  }, []);

  if (loading) return <p>Loading assets...</p>;
  if (error) return <p className="error">{error}</p>;

  return (
    <div className="checkout-module">
      <h2>Available Assets</h2>
      {assets.length === 0 ? (
        <p>No assets available</p>
      ) : (
        <table className="assets-table">
          <thead>
            <tr>
              <th>Asset Code</th>
              <th>Name</th>
              <th>Type</th>
              <th>Department</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            {assets.map((asset) => (
              <tr key={asset.id}>
                <td>{asset.asset_code}</td>
                <td>{asset.name}</td>
                <td>{asset.asset_type_name}</td>
                <td>{asset.department_name}</td>
                <td>{asset.status}</td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  );
}