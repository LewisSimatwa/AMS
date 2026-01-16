import { useState, useEffect } from "react";
import { 
  Building2, 
  Plus, 
  Search, 
  Edit2, 
  Users, 
  Package,
  X,
  Save,
  UserPlus
} from "lucide-react";
import "../../styles/SuperAdmin/institution.css";

export default function InstitutionManagement() {
  const [institutions, setInstitutions] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState("");
  const [showModal, setShowModal] = useState(false);
  const [showAdminModal, setShowAdminModal] = useState(false);
  const [modalMode, setModalMode] = useState("create");
  const [selectedInstitution, setSelectedInstitution] = useState(null);
  const [formData, setFormData] = useState({
    name: "",
    code: "",
    address: "",
    contact_email: ""
  });
  const [adminFormData, setAdminFormData] = useState({
    first_name: "",
    last_name: "",
    email: "",
    username: "",
    password: ""
  });

  useEffect(() => {
    fetchInstitutions();
  }, []);

  const fetchInstitutions = async () => {
    try {
      const token = localStorage.getItem("token");
      const response = await fetch("http://localhost:8000/api/super_admin/institutions", {  
        headers: {
          "Authorization": `Bearer ${token}`
        }
      });
      const data = await response.json();
      setInstitutions(data.institutions || []);
    } catch (error) {
      console.error("Failed to fetch institutions:", error);
    } finally {
      setLoading(false);
    }
  };

  const handleCreateInstitution = () => {
    setModalMode("create");
    setFormData({
      name: "",
      code: "",
      address: "",
      contact_email: ""
    });
    setShowModal(true);
  };

  const handleEditInstitution = (institution) => {
    setModalMode("edit");
    setSelectedInstitution(institution);
    setFormData({
      name: institution.name,
      code: institution.code,
      address: institution.address || "",
      contact_email: institution.contact_email || ""
    });
    setShowModal(true);
  };

  const handleSubmitInstitution = async () => {
    if (!formData.name.trim()) {
      alert("Institution name is required");
      return;
    }

    try {
      const token = localStorage.getItem("token");
      const url = modalMode === "create" 
        ? "http://localhost:8000/api/super_admin/institutions"
        : `http://localhost:8000/api/super_admin/institutions?id=${selectedInstitution.id}`;
      
      const response = await fetch(url, {
        method: modalMode === "create" ? "POST" : "PUT",
        headers: {
          "Authorization": `Bearer ${token}`,
          "Content-Type": "application/json"
        },
        body: JSON.stringify(formData)
      });

      if (response.ok) {
        setShowModal(false);
        fetchInstitutions();
        alert(modalMode === "create" ? "Institution created successfully" : "Institution updated successfully");
      } else {
        const errorData = await response.json();
        alert(`Failed to save institution: ${errorData.error || 'Unknown error'}`);
      }
    } catch (error) {
      console.error("Failed to save institution:", error);
      alert("Failed to save institution");
    }
  };

  const handleCreateAdmin = (institution) => {
    setSelectedInstitution(institution);
    setAdminFormData({
      first_name: "",
      last_name: "",
      email: "",
      username: "",
      password: ""
    });
    setShowAdminModal(true);
  };

  const handleSubmitAdmin = async () => {
    if (!adminFormData.first_name || !adminFormData.last_name || !adminFormData.email || 
        !adminFormData.username || !adminFormData.password) {
      alert("All fields are required");
      return;
    }

    if (adminFormData.password.length < 6) {
      alert("Password must be at least 6 characters");
      return;
    }

    try {
      const token = localStorage.getItem("token");
      const response = await fetch("http://localhost:8000/api/super-admin/create-admin", {
        method: "POST",
        headers: {
          "Authorization": `Bearer ${token}`,
          "Content-Type": "application/json"
        },
        body: JSON.stringify({
          ...adminFormData,
          institution_id: selectedInstitution.id
        })
      });

      if (response.ok) {
        setShowAdminModal(false);
        alert("Admin created successfully");
      } else {
        const errorData = await response.json();
        alert(`Failed to create admin: ${errorData.error || 'Unknown error'}`);
      }
    } catch (error) {
      console.error("Failed to create admin:", error);
      alert("Failed to create admin");
    }
  };

  const filteredInstitutions = institutions.filter(inst =>
    inst.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    inst.code?.toLowerCase().includes(searchTerm.toLowerCase())
  );

  if (loading) {
    return (
      <div className="loading-spinner">
        <div className="spinner"></div>
      </div>
    );
  }

  return (
    <div className="institution-management">
      <div className="institution-header">
        <div>
          <h1>Institution Management</h1>
          <p>Manage all institutions in the system</p>
        </div>
        <button onClick={handleCreateInstitution} className="btn-create">
          <Plus size={20} />
          <span>Create Institution</span>
        </button>
      </div>

      <div className="search-container">
        <Search className="search-icon" size={20} />
        <input
          type="text"
          placeholder="Search institutions..."
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          className="search-input"
        />
      </div>

      <div className="institution-grid">
        {filteredInstitutions.map((institution) => (
          <div key={institution.id} className="institution-card">
            <div className="institution-card-content">
              <div className="card-header">
                <div className="institution-info">
                  <div className="institution-icon">
                    <Building2 size={24} />
                  </div>
                  <div className="institution-details">
                    <h3>{institution.name}</h3>
                    <p>{institution.code}</p>
                  </div>
                </div>
              </div>

              <div className="institution-stats">
                <div className="stat-item">
                  <Users size={16} />
                  <span>{institution.user_count || 0} Users</span>
                </div>
                <div className="stat-item">
                  <Package size={16} />
                  <span>{institution.asset_count || 0} Assets</span>
                </div>
              </div>

              <div className="card-actions">
                <button
                  onClick={() => handleEditInstitution(institution)}
                  className="btn-edit"
                >
                  <Edit2 size={16} />
                  <span>Edit</span>
                </button>
                <button
                  onClick={() => handleCreateAdmin(institution)}
                  className="btn-create-admin"
                  title="Create Admin"
                >
                  <UserPlus size={16} />
                </button>
              </div>
            </div>
          </div>
        ))}
      </div>

      {filteredInstitutions.length === 0 && (
        <div className="empty-state">
          <Building2 size={48} />
          <h3>No institutions found</h3>
          <p>Get started by creating a new institution.</p>
        </div>
      )}

      {showModal && (
        <div className="modal-overlay" onClick={() => setShowModal(false)}>
          <div className="modal" onClick={(e) => e.stopPropagation()}>
            <div className="modal-header">
              <h2>{modalMode === "create" ? "Create Institution" : "Edit Institution"}</h2>
              <button onClick={() => setShowModal(false)} className="btn-close">
                <X size={24} />
              </button>
            </div>
            <div className="modal-form">
              <div className="form-row">
                <div className="form-group">
                  <label className="form-label">Institution Name *</label>
                  <input
                    type="text"
                    value={formData.name}
                    onChange={(e) => setFormData({...formData, name: e.target.value})}
                    className="form-input"
                  />
                </div>
                <div className="form-group">
                  <label className="form-label">Code</label>
                  <input
                    type="text"
                    value={formData.code}
                    onChange={(e) => setFormData({...formData, code: e.target.value})}
                    className="form-input"
                  />
                </div>
              </div>
              <div className="form-group">
                <label className="form-label">Address</label>
                <input
                  type="text"
                  value={formData.address}
                  onChange={(e) => setFormData({...formData, address: e.target.value})}
                  className="form-input"
                />
              </div>
              <div className="form-group">
                <label className="form-label">Contact Email</label>
                <input
                  type="email"
                  value={formData.contact_email}
                  onChange={(e) => setFormData({...formData, contact_email: e.target.value})}
                  className="form-input"
                />
              </div>
              <div className="form-actions">
                <button onClick={() => setShowModal(false)} className="btn-cancel">
                  Cancel
                </button>
                <button onClick={handleSubmitInstitution} className="btn-save">
                  <Save size={18} />
                  <span>Save</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {showAdminModal && (
        <div className="modal-overlay" onClick={() => setShowAdminModal(false)}>
          <div className="modal" onClick={(e) => e.stopPropagation()} style={{maxWidth: '28rem'}}>
            <div className="modal-header">
              <h2>Create Admin for {selectedInstitution?.name}</h2>
              <button onClick={() => setShowAdminModal(false)} className="btn-close">
                <X size={24} />
              </button>
            </div>
            <div className="modal-form">
              <div className="form-row">
                <div className="form-group">
                  <label className="form-label">First Name *</label>
                  <input
                    type="text"
                    value={adminFormData.first_name}
                    onChange={(e) => setAdminFormData({...adminFormData, first_name: e.target.value})}
                    className="form-input"
                  />
                </div>
                <div className="form-group">
                  <label className="form-label">Last Name *</label>
                  <input
                    type="text"
                    value={adminFormData.last_name}
                    onChange={(e) => setAdminFormData({...adminFormData, last_name: e.target.value})}
                    className="form-input"
                  />
                </div>
              </div>
              <div className="form-group">
                <label className="form-label">Email *</label>
                <input
                  type="email"
                  value={adminFormData.email}
                  onChange={(e) => setAdminFormData({...adminFormData, email: e.target.value})}
                  className="form-input"
                />
              </div>
              <div className="form-group">
                <label className="form-label">Username *</label>
                <input
                  type="text"
                  value={adminFormData.username}
                  onChange={(e) => setAdminFormData({...adminFormData, username: e.target.value})}
                  className="form-input"
                />
              </div>
              <div className="form-group">
                <label className="form-label">Password *</label>
                <input
                  type="password"
                  value={adminFormData.password}
                  onChange={(e) => setAdminFormData({...adminFormData, password: e.target.value})}
                  className="form-input"
                  minLength={6}
                />
              </div>
              <div className="form-actions">
                <button onClick={() => setShowAdminModal(false)} className="btn-cancel">
                  Cancel
                </button>
                <button onClick={handleSubmitAdmin} className="btn-save">
                  <UserPlus size={18} />
                  <span>Create Admin</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}