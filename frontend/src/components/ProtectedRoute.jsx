import { Navigate, Outlet } from "react-router-dom";

export default function ProtectedRoute() {
  const token = localStorage.getItem("token");
  
  console.log("=== PROTECTED ROUTE CHECK ===");
  console.log("Token exists:", token ? "YES" : "NO");
  console.log("Current path:", window.location.pathname);
  
  if (!token) {
    console.log("No token found, redirecting to login");
    return <Navigate to="/login" replace />;
  }
  
  console.log("Token found, allowing access");
  return <Outlet />;
}