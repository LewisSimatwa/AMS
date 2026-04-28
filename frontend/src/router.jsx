import { BrowserRouter, Routes, Route, Navigate } from "react-router-dom";
import Login from "./features/auth/Login";
import CreateAccount from "./features/auth/CreateAccount";
import ForgotPassword from "./features/auth/ForgotPassword";
import Dashboard from "./features/dashboard/Dashboard";
import AssetsDetails from "./features/assets/AssetsDetails";
import PredictiveAnalytics from "./features/analytics/PredictiveAnalytics";
import Report from "./features/reports/ReportsPage";
import UserManagement from "./features/users/UserManagement";
import DepartmentManagement from "./features/users/DepartmentManagement";
import MainLayout from "./layouts/MainLayout";
import Maintenance from "./features/maintenance/Maintenance";
import AuditLogs from "./features/audit/AuditLogs";
import ProtectedRoute from "./components/ProtectedRoute";
import CheckOut from "./features/assets/Checkout";
// Super-admin routes
import SuperAdminLayout from './layouts/SuperAdminLayout';
import SuperAdminDashboard from './features/superAdmin/Dashboard';
import SuperAdminInstitutions from './features/superAdmin/Institutions';
import SuperAdminImportCSV from './features/superAdmin/ImportCSV';
import SuperAdminAssets from './features/superAdmin/Assets';
import SuperAdminReportsAndAudits from './features/superAdmin/ReportsAndAudit';
import SuperAdminSettings from './features/superAdmin/Settings';

/** Reads the user object stored at login and checks their role. */
function getUserRole() {
  try {
    const user = JSON.parse(localStorage.getItem("user") || "{}");
    return user?.role?.toLowerCase() ?? "";
  } catch {
    return "";
  }
}

/**
 * Wraps a route so only users whose role is in `allowedRoles` can access it.
 * Everyone else is redirected to /dashboard.
 */
function RoleRoute({ allowedRoles, element }) {
  const role = getUserRole();
  return allowedRoles.includes(role) ? element : <Navigate to="/dashboard" replace />;
}

export default function AppRouter() {
  return (
    <BrowserRouter>
      <Routes>
        {/* Public routes */}
        <Route path="/" element={<Login />} />
        <Route path="/login" element={<Login />} />
        <Route path="/create-account" element={<CreateAccount />} />
        <Route path="/forgot-password" element={<ForgotPassword />} />

        {/* Protected routes */}
        <Route element={<ProtectedRoute />}>

          {/* Regular routes — accessible to all authenticated users */}
          <Route element={<MainLayout />}>
            <Route path="/dashboard"   element={<Dashboard />} />
            <Route path="/assets"      element={<AssetsDetails />} />
            <Route path="/checkout"    element={<CheckOut />} />
            <Route path="/maintenance" element={<Maintenance />} />
            <Route path="/analytics"   element={<PredictiveAnalytics />} />
            <Route path="/report"      element={<Report />} />
            <Route path="/audit"       element={<AuditLogs />} />

            {/* Admin-only routes — redirect to /dashboard for non-admins */}
            <Route
              path="/usermanagement"
              element={
                <RoleRoute
                  allowedRoles={["admin"]}
                  element={<UserManagement />}
                />
              }
            />
            <Route
              path="/departments"
              element={
                <RoleRoute
                  allowedRoles={["admin"]}
                  element={<DepartmentManagement />}
                />
              }
            />

            {/* Legacy /users alias — keep so old bookmarks don't 404 */}
            <Route
              path="/users"
              element={
                <RoleRoute
                  allowedRoles={["admin"]}
                  element={<UserManagement />}
                />
              }
            />
          </Route>

          {/* Super-admin routes */}
          <Route path="/super-admin" element={<SuperAdminLayout />}>
            <Route index                element={<SuperAdminDashboard />} />
            <Route path="dashboard"     element={<SuperAdminDashboard />} />
            <Route path="institutions"  element={<SuperAdminInstitutions />} />
            <Route path="importcsv"     element={<SuperAdminImportCSV />} />
            <Route path="assets"        element={<SuperAdminAssets />} />
            <Route path="reports"       element={<SuperAdminReportsAndAudits />} />
            <Route path="settings"      element={<SuperAdminSettings />} />
          </Route>

        </Route>
      </Routes>
    </BrowserRouter>
  );
}