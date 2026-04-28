import { useState, useEffect } from "react";
import { Outlet, useNavigate } from "react-router-dom";
import Sidebar from "./Sidebar";
import "../styles/MainLayout.css";

export default function MainLayout() {
  const [isSidebarOpen, setIsSidebarOpen] = useState(false);
  const navigate = useNavigate();

  const user = JSON.parse(localStorage.getItem("user")) || { name: "!User!" };

  useEffect(() => {
    const handlePopState = () => {
      const user = localStorage.getItem("user");
      if (!user) {
        navigate("/login", { replace: true });
      }
    };

    window.addEventListener("popstate", handlePopState);
    return () => window.removeEventListener("popstate", handlePopState);
  }, [navigate]);

  const handleLogout = () => {
    localStorage.removeItem("user");
    localStorage.removeItem("token");

    window.history.pushState(null, null, window.location.href);
    navigate("/login", { replace: true });
  };

  return (
    <div className="app-layout">
      <Sidebar
        isOpen={isSidebarOpen}
        setIsOpen={setIsSidebarOpen}
        user={user}
        onLogout={handleLogout}
      />

      <main className={`main-content ${isSidebarOpen ? "shifted" : ""}`}>
        <Outlet />
      </main>
    </div>
  );
}
