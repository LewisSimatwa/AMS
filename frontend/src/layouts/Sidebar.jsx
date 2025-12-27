import { useNavigate, useLocation } from "react-router-dom";
import { LayoutDashboard, Package, FileText, Wrench, BarChart3, ClipboardList, PanelLeftClose, PanelLeft } from "lucide-react";
import "../styles/Sidebar.css";

export default function Sidebar({ isOpen, setIsOpen, user, onLogout }) {
  const navigate = useNavigate();
  const location = useLocation();

  const menuItems = [
    { path: "/dashboard", label: "Dashboard", icon: LayoutDashboard },
    { path: "/assets", label: "Assets", icon: Package },
    { path: "/reports", label: "Reports", icon: FileText },
    { path: "/maintanance", label: "Maintenance", icon: Wrench },
    { path: "/analytics", label: "Analytics", icon: BarChart3 },
    { path: "/audit", label: "Audit Logs", icon: ClipboardList },
  ];

  const handleLogout = () => {
    localStorage.clear();
    navigate("/login");
  };

  const isActive = (path) => location.pathname === path;

  return (
    <>
      {/* Overlay for mobile */}
      {isOpen && <div className="sidebar-overlay" onClick={() => setIsOpen(false)} />}

      {/* Burger button outside sidebar for desktop */}
      <button
        className="burger-btn"
        onClick={() => setIsOpen(!isOpen)}
        aria-label="Toggle sidebar"
      >
        {isOpen ? <PanelLeftClose size={20} /> : <PanelLeft size={20} />}
      </button>

      {/* Sidebar */}
      <aside className={`sidebar ${isOpen ? "open" : ""}`}>
        <div className="sidebar-header">
          <span className="logo-icon"> </span>
          <span className="logo-text">Asset Manager</span>
        </div>

        <nav className="sidebar-nav">
          <ul className="nav-list">
            {menuItems.map((item) => {
              const IconComponent = item.icon;
              return (
                <li key={item.path}>
                  <button
                    className={`nav-item ${isActive(item.path) ? "active" : ""}`}
                    onClick={() => {
                      navigate(item.path);
                      if (window.innerWidth <= 768) setIsOpen(false);
                    }}
                  >
                    <span className="nav-icon">
                      <IconComponent size={20} />
                    </span>
                    <span className="nav-label">{item.label}</span>
                  </button>
                </li>
              );
            })}
          </ul>
        </nav>

        <div className="sidebar-footer">
          <div className="user-profile">
            <div className="user-avatar">{(user?.email?.[0] || "U").toUpperCase()}</div>
            <div className="user-info">
              <div className="user-name">{user?.email || "User"}</div>
              <div className="user-role">{user?.role || "Free plan"}</div>
            </div>
          </div>
          <button className="logout-btn" onClick={handleLogout}>🚪 Logout</button>
        </div>
      </aside>
    </>
  );
}
