import { useNavigate, useLocation } from "react-router-dom";
import { LayoutDashboard, Package, FileText, Wrench, BarChart3, ClipboardList, PanelLeftClose, PanelLeft, ArrowLeftRight, ShieldCheck, User, HistoryIcon, Building2 } from "lucide-react";
import "../styles/Sidebar.css";

export default function Sidebar({ isOpen, setIsOpen, user, onLogout }) {
  const navigate = useNavigate();
  const location = useLocation();

  const isAdmin = user?.role?.toLowerCase() === "admin";

  const menuItems = [
    { path: "/dashboard",      label: "Dashboard",       icon: LayoutDashboard },
    { path: "/assets",         label: "Assets",          icon: Package },
    { path: "/checkout",       label: "Check In/Out",    icon: ArrowLeftRight },
    { path: "/maintenance",    label: "Maintenance",     icon: Wrench },
    { path: "/analytics",      label: "Analytics",       icon: BarChart3 },
    { path: "/report",         label: "Reports",         icon: FileText },
    { path: "/audit",          label: "Audit Logs",      icon: HistoryIcon },

    // Admin-only items
    { path: "/usermanagement", label: "User Management", icon: User,      adminOnly: true },
    { path: "/departments",    label: "Departments",     icon: Building2, adminOnly: true },
  ];

  const visibleItems = menuItems.filter(item => !item.adminOnly || isAdmin);

  const handleLogout = () => {
    localStorage.clear();
    navigate("/login");
  };

  const isActive = (path) => location.pathname === path;

  return (
    <>
      {isOpen && <div className="sidebar-overlay" onClick={() => setIsOpen(false)} />}

      <button
        className="burger-btn"
        onClick={() => setIsOpen(!isOpen)}
        aria-label="Toggle sidebar"
      >
        {isOpen ? <PanelLeftClose size={20} /> : <PanelLeft size={20} />}
      </button>

      <aside className={`sidebar ${isOpen ? "open" : ""}`}>
        <div className="sidebar-header">
          <span className="logo-icon"> </span>
          <span className="logo-text">Asset Manager</span>
        </div>

        <nav className="sidebar-nav">
          {/* Regular menu items */}
          <ul className="nav-list">
            {visibleItems.filter(item => !item.adminOnly).map((item) => {
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
                    <span className="nav-icon"><IconComponent size={20} /></span>
                    <span className="nav-label">{item.label}</span>
                  </button>
                </li>
              );
            })}
          </ul>

          {/* Admin section — only rendered for admins */}
          {isAdmin && (
            <div className="nav-section">
              <span className="nav-section-label">Administration</span>
              <ul className="nav-list">
                {visibleItems.filter(item => item.adminOnly).map((item) => {
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
                        <span className="nav-icon"><IconComponent size={20} /></span>
                        <span className="nav-label">{item.label}</span>
                      </button>
                    </li>
                  );
                })}
              </ul>
            </div>
          )}
        </nav>

        <div className="sidebar-footer">
          <div className="user-profile">
            <div className="user-avatar">{(user?.email?.[0] || "U").toUpperCase()}</div>
            <div className="user-info">
              <div className="user-name">{user?.email || "User"}</div>
              <div className="user-role">{user?.role || "-"}</div>
            </div>
          </div>
          <button className="logout-btn" onClick={handleLogout}>🚪 Logout</button>
        </div>
      </aside>
    </>
  );
}