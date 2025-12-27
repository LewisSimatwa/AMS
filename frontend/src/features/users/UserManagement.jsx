import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import "../../styles/Users.css";

export default function Users() {
  const navigate = useNavigate();
  const [users, setUsers] = useState([]);
  const [newUser, setNewUser] = useState({
    username: "",
    email: "",
    password: "",
    role: "staff",
  });
  const [search, setSearch] = useState("");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [successMsg, setSuccessMsg] = useState("");

  const token = localStorage.getItem("token");
  const institutionId = localStorage.getItem("institutionId");

  useEffect(() => {
    if (!token || !institutionId) {
      navigate("/login");
      return;
    }

    fetchUsers();
  }, [token, institutionId]);

  async function fetchUsers() {
    setLoading(true);
    setError("");

    try {
      const headers = {
        Authorization: `Bearer ${token}`,
        "X-Institution-ID": institutionId,
      };

      const response = await fetch(
        `http://localhost:8000/api/users?institution_id=${institutionId}`,
        { headers }
      );

      if (!response.ok) {
        throw new Error("Failed to fetch users");
      }

      const data = await response.json();
      setUsers(data.users || []);
    } catch (err) {
      console.error("Fetch users error:", err);
      setError(err.message || "Failed to load users");
    } finally {
      setLoading(false);
    }
  }

  async function handleAddUser(e) {
    e.preventDefault();
    setError("");
    setSuccessMsg("");

    try {
      const headers = {
        "Content-Type": "application/json",
        Authorization: `Bearer ${token}`,
        "X-Institution-ID": institutionId,
      };

      const response = await fetch("http://localhost:8000/api/users/register", {
        method: "POST",
        headers,
        body: JSON.stringify({
          ...newUser,
          institution_id: institutionId,
        }),
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.detail || "Failed to add user");
      }

      setSuccessMsg("User added successfully!");
      setNewUser({ username: "", email: "", password: "", role: "staff" });
      fetchUsers(); // Refresh the list
    } catch (err) {
      console.error("Add user error:", err);
      setError(err.message || "Failed to add user");
    }
  }

  async function toggleUserStatus(userId, currentStatus) {
    try {
      const headers = {
        "Content-Type": "application/json",
        Authorization: `Bearer ${token}`,
        "X-Institution-ID": institutionId,
      };

      const newStatus = currentStatus === "active" ? "inactive" : "active";

      const response = await fetch(
        `http://localhost:8000/api/users/${userId}/status`,
        {
          method: "PUT",
          headers,
          body: JSON.stringify({ status: newStatus }),
        }
      );

      if (!response.ok) {
        throw new Error("Failed to update user status");
      }

      // Update local state
      setUsers((prev) =>
        prev.map((u) => (u.id === userId ? { ...u, status: newStatus } : u))
      );
    } catch (err) {
      console.error("Toggle status error:", err);
      alert(err.message || "Failed to update user status");
    }
  }

  const filteredUsers = users.filter(
    (u) =>
      u.username?.toLowerCase().includes(search.toLowerCase()) ||
      u.email?.toLowerCase().includes(search.toLowerCase())
  );

  if (loading) {
    return (
      <div className="users-page">
        <div className="loading-spinner">Loading users...</div>
      </div>
    );
  }

  return (
    <div className="users-page">
      <div className="users-header">
        <h1>User Management</h1>
        <p>View, add, and manage system users</p>
      </div>

      {error && (
        <div className="error-message">
          {error}
          <button onClick={() => setError("")}>×</button>
        </div>
      )}

      {successMsg && (
        <div className="success-message">
          {successMsg}
          <button onClick={() => setSuccessMsg("")}>×</button>
        </div>
      )}

      {/* Add new user */}
      <form className="user-form" onSubmit={handleAddUser}>
        <input
          type="text"
          placeholder="Username *"
          value={newUser.username}
          required
          onChange={(e) => setNewUser({ ...newUser, username: e.target.value })}
        />
        <input
          type="email"
          placeholder="Email *"
          value={newUser.email}
          required
          onChange={(e) => setNewUser({ ...newUser, email: e.target.value })}
        />
        <input
          type="password"
          placeholder="Password *"
          value={newUser.password}
          required
          minLength="6"
          onChange={(e) => setNewUser({ ...newUser, password: e.target.value })}
        />
        <select
          value={newUser.role}
          onChange={(e) => setNewUser({ ...newUser, role: e.target.value })}
        >
          <option value="admin">Admin</option>
          <option value="staff">Staff</option>
          <option value="viewer">Viewer</option>
        </select>
        <button type="submit">Add User</button>
      </form>

      {/* Search */}
      <input
        type="text"
        placeholder="Search users..."
        className="user-search"
        value={search}
        onChange={(e) => setSearch(e.target.value)}
      />

      {/* Users Table */}
      <div className="table-summary">
        <p>
          Showing <strong>{filteredUsers.length}</strong> of{" "}
          <strong>{users.length}</strong> users
        </p>
      </div>

      <table className="users-table">
        <thead>
          <tr>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Created</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          {filteredUsers.length === 0 ? (
            <tr>
              <td colSpan="6" style={{ textAlign: "center" }}>
                {search ? `No users found matching "${search}"` : "No users found"}
              </td>
            </tr>
          ) : (
            filteredUsers.map((user) => (
              <tr key={user.id}>
                <td>{user.username}</td>
                <td>{user.email}</td>
                <td>
                  <span className={`role-badge ${user.role}`}>
                    {user.role}
                  </span>
                </td>
                <td>
                  <span className={`status-badge ${user.status}`}>
                    {user.status}
                  </span>
                </td>
                <td>{new Date(user.created_at).toLocaleDateString()}</td>
                <td>
                  <button
                    onClick={() => toggleUserStatus(user.id, user.status)}
                    className={user.status === "active" ? "deactivate-btn" : "activate-btn"}
                  >
                    {user.status === "active" ? "Deactivate" : "Activate"}
                  </button>
                </td>
              </tr>
            ))
          )}
        </tbody>
      </table>
    </div>
  );
}