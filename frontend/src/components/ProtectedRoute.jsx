import { Navigate, Outlet } from "react-router-dom";

export default function ProtectedRoute() {
  const token = localStorage.getItem("token");

  if (!token) {
    // No token, redirect to login
    return <Navigate to="/login" replace />;
  }

  // Token exists, render child routes
  return <Outlet />;
}
