import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { TrendingUp, TrendingDown, Users, Package } from "lucide-react";
import "../../styles/Analytics.css";

export default function Analytics() {
  const navigate = useNavigate();
  const [timeRange, setTimeRange] = useState("week");
  const [stats, setStats] = useState({
    totalAssets: 0,
    assetsInUse: 0,
    assetsInRepair: 0,
    totalUsers: 0,
  });
  const [riskScores, setRiskScores] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  const token = localStorage.getItem("token");
  const institutionId = localStorage.getItem("institutionId");

  useEffect(() => {
    if (!token || !institutionId) {
      navigate("/login");
      return;
    }

    fetchAnalyticsData();
  }, [token, institutionId, timeRange]);

  async function fetchAnalyticsData() {
    setLoading(true);
    setError("");

    try {
      const headers = {
        Authorization: `Bearer ${token}`,
        "X-Institution-ID": institutionId,
      };

      // Fetch assets
      const assetsRes = await fetch(
        `http://localhost:8000/api/assets?institution_id=${institutionId}`,
        { headers }
      );

      if (!assetsRes.ok) throw new Error("Failed to fetch assets");

      const assetsData = await assetsRes.json();
      const assets = assetsData.assets || [];

      // Calculate stats
      const inUse = assets.filter((a) => a.status === "on_loan").length;
      const inRepair = assets.filter((a) => a.status === "maintenance").length;

      // Fetch risk scores
      let scores = [];
      try {
        const riskRes = await fetch(
          `http://localhost:8000/api/analytics/risk-scores?institution_id=${institutionId}`,
          { headers }
        );

        if (riskRes.ok) {
          const riskData = await riskRes.json();
          scores = riskData.scores || [];
        }
      } catch (err) {
        console.warn("Risk scores not available");
      }

      // Fetch users count (if you have a users endpoint)
      let userCount = 0;
      try {
        const usersRes = await fetch(
          `http://localhost:8000/api/users?institution_id=${institutionId}`,
          { headers }
        );

        if (usersRes.ok) {
          const usersData = await usersRes.json();
          userCount = usersData.users?.length || 0;
        }
      } catch (err) {
        console.warn("Users data not available");
      }

      setStats({
        totalAssets: assets.length,
        assetsInUse: inUse,
        assetsInRepair: inRepair,
        totalUsers: userCount,
      });

      setRiskScores(scores);
    } catch (err) {
      console.error("Analytics error:", err);
      setError(err.message || "Failed to load analytics data");
    } finally {
      setLoading(false);
    }
  }

  const statsCards = [
    {
      title: "Total Assets",
      value: stats.totalAssets,
      icon: <Package />,
      color: "#3b82f6",
    },
    {
      title: "Assets in Use",
      value: stats.assetsInUse,
      icon: <TrendingUp />,
      color: "#10b981",
    },
    {
      title: "Assets in Repair",
      value: stats.assetsInRepair,
      icon: <TrendingDown />,
      color: "#ef4444",
    },
    {
      title: "Total Users",
      value: stats.totalUsers,
      icon: <Users />,
      color: "#8b5cf6",
    },
  ];

  if (loading) {
    return (
      <div className="analytics-page">
        <div className="loading-spinner">Loading analytics...</div>
      </div>
    );
  }

  return (
    <div className="analytics-page">
      <div className="analytics-header">
        <h1>Analytics Dashboard</h1>
        <p>Overview of asset and user statistics</p>
      </div>

      {error && (
        <div className="error-banner">
          <p>{error}</p>
          <button onClick={fetchAnalyticsData}>Retry</button>
        </div>
      )}

      <div className="time-range">
        <button
          className={timeRange === "week" ? "active" : ""}
          onClick={() => setTimeRange("week")}
        >
          This Week
        </button>
        <button
          className={timeRange === "month" ? "active" : ""}
          onClick={() => setTimeRange("month")}
        >
          This Month
        </button>
        <button
          className={timeRange === "year" ? "active" : ""}
          onClick={() => setTimeRange("year")}
        >
          This Year
        </button>
      </div>

      <div className="stats-cards">
        {statsCards.map((stat) => (
          <div key={stat.title} className="card" style={{ borderLeft: `4px solid ${stat.color}` }}>
            <div className="card-icon" style={{ color: stat.color }}>
              {stat.icon}
            </div>
            <div className="card-info">
              <h2>{stat.value}</h2>
              <p>{stat.title}</p>
            </div>
          </div>
        ))}
      </div>

      {/* Risk Scores Table */}
      {riskScores.length > 0 && (
        <div className="risk-scores-section">
          <h2>Risk Analysis</h2>
          <table className="risk-table">
            <thead>
              <tr>
                <th>Asset Code</th>
                <th>Asset Name</th>
                <th>Risk Level</th>
                <th>Risk Score</th>
              </tr>
            </thead>
            <tbody>
              {riskScores.slice(0, 10).map((asset) => (
                <tr key={asset.id}>
                  <td>{asset.asset_code}</td>
                  <td>{asset.name}</td>
                  <td>
                    <span className={`risk-badge ${asset.risk_level?.toLowerCase()}`}>
                      {asset.risk_level}
                    </span>
                  </td>
                  <td>{((asset.risk_score || 0) * 100).toFixed(0)}%</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}