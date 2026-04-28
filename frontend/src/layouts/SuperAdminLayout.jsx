import { useState, useEffect } from "react";
import { Outlet, Link, useNavigate, useLocation } from "react-router-dom";
import { 
  LayoutDashboard, 
  Building2, 
  Package, 
  LogOut, 
  Menu, 
  X,
  Shield,
  Import,
  Settings2Icon,
  SettingsIcon,
  LineSquiggleIcon
} from "lucide-react";
import "../styles/SuperAdmin/layout.css";

export default function SuperAdminLayout() {
  const navigate = useNavigate();
  const location = useLocation();
  const [sidebarOpen, setSidebarOpen] = useState(true);
  const [user, setUser] = useState(null);

  useEffect(() => {
    // Check authentication and super admin role
    const token = localStorage.getItem("token");
    const userData = localStorage.getItem("user");
    
    if (!token || !userData) {
      navigate("/login");
      return;
    }

    const parsedUser = JSON.parse(userData);
    
    // Verify super admin role
    if (parsedUser.role !== "super_admin") {
      navigate("/dashboard");
      return;
    }

    setUser(parsedUser);
  }, [navigate]);

  const handleLogout = () => {
    sessionStorage.clear();
    navigate("/login");
  };

  const menuItems = [
    {
      path: "/super-admin/dashboard",
      icon: LayoutDashboard,
      label: "Dashboard"
    },
    {
      path: "/super-admin/institutions",
      icon: Building2,
      label: "Institutions"
    },
    // {
    //   path: "/super-admin/assets",
    //   icon: Package,
    //   label: "Global Assets"
    // },
    {
      path: "/super-admin/importcsv",
      icon: Import,
      label: "Import CSV"
    },
    {
      path: "/super-admin/reports",
      icon: Shield,
      label: "Reports and Audits"
    },
    // {
    //   path: "/super-admin/settings",
    //   icon: SettingsIcon,
    //   label: "System Settings"
    // }
  ];

  const isActive = (path) => location.pathname === path;

  if (!user) {
    return (
      <div className="loading-center">
        <div className="loading-spinner"></div>
      </div>
    );
  }

  return (
    <div className="superadmin-layout">
      {/* Sidebar */}
      <aside className={`superadmin-sidebar ${sidebarOpen ? 'sidebar-expanded' : 'sidebar-collapsed'}`}>
        {/* Header */}
        <div className="sidebar-header">
          {sidebarOpen ? (
            <>
              <div className="sidebar-logo">
                <Shield className="sidebar-logo-icon" size={28} />
                <span className="sidebar-logo-text">Super Admin</span>
              </div>
              <button
                onClick={() => setSidebarOpen(false)}
                className="sidebar-toggle"
              >
                <X size={20} />
              </button>
            </>
          ) : (
            <button
              onClick={() => setSidebarOpen(true)}
              className="sidebar-toggle"
            >
              <Menu size={20} />
            </button>
          )}
        </div>

        {/* Navigation */}
        <nav className="sidebar-nav">
          <div className="nav-items">
            {menuItems.map((item) => (
              <Link
                key={item.path}
                to={item.path}
                className={`nav-item ${isActive(item.path) ? 'active' : ''}`}
                data-tooltip={item.label}
              >
                <item.icon size={20} className="nav-item-icon" />
                <span className="nav-item-label">{item.label}</span>
              </Link>
            ))}
          </div>
        </nav>

        {/* User Info & Logout */}
        <div className="sidebar-user">
          {sidebarOpen && (
            <div className="user-info">
              <p className="user-name">{user.first_name} {user.last_name}</p>
              <p className="user-email">{user.email}</p>
            </div>
          )}
          <button onClick={handleLogout} className="logout-button">
            <LogOut size={18} />
            <span>Logout</span>
          </button>
        </div>
      </aside>

      {/* Main Content */}
      <main className="superadmin-main">
        <Outlet />
      </main>
    </div>
  );
}