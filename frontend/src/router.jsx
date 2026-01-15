import { BrowserRouter, Routes, Route } from "react-router-dom";
import Login from "./features/auth/Login";
import CreateAccount from "./features/auth/CreateAccount";
import ForgotPassword from "./features/auth/ForgotPassword";
//import SelectInstitution from "./features/institutions/SelectInstitution";
import Dashboard from "./features/dashboard/Dashboard";
import AssetsDetails from "./features/assets/AssetsDetails";
import PredictiveAnalytics from "./features/analytics/PredictiveAnalytics";
import Reports from "./features/reports/ReportsPage";
import UserManagement from "./features/users/UserManagement";
import MainLayout from "./layouts/MainLayout";
import Maintenance from "./features/maintenance/Maintenance";
import AuditLogs from "./features/audit/AuditLogs";
import ProtectedRoute from "./components/ProtectedRoute";
import CheckOut from "./features/assets/Checkout";
import RetireAsset from "./features/assets/RetireAsset";
//super-admin routes
import SuperAdminLayout from './layouts/SuperAdminLayout';
import SuperAdminDashboard from './features/superAdmin/Dashboard';
import SuperAdminInstitutions from './features/superAdmin/Institutions';
//import SuperAdminUsers from './features/superAdmin/Users';
import SuperAdminAssets from './features/superAdmin/Assets';
//import SuperAdminReports from './features/superAdmin/Reports';



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
          {/* Regular user routes with MainLayout */}
          <Route element={<MainLayout />}>
            <Route path="/dashboard" element={<Dashboard />} />
            <Route path="/assets" element={<AssetsDetails />} />
            <Route path="/reports" element={<Reports />} />
            <Route path="/maintenance" element={<Maintenance />} />
            <Route path="/analytics" element={<PredictiveAnalytics />} />
            <Route path="/users" element={<UserManagement />} />
            <Route path="/audit" element={<AuditLogs />} />
            <Route path="/checkout" element={<CheckOut/>} />
            <Route path="/usermanagement" element={<UserManagement/>} />
          </Route>

          {/* Super-admin routes with SuperAdminLayout */}
          <Route path="/super-admin" element={<SuperAdminLayout />}>
            <Route index element={<SuperAdminDashboard />} />
            <Route path="dashboard" element={<SuperAdminDashboard />} />
            <Route path="institutions" element={<SuperAdminInstitutions />} />
            {/*<Route path="users" element={<SuperAdminUsers />} />*/}
            <Route path="assets" element={<SuperAdminAssets />} />
            {/*<Route path="reports" element={<SuperAdminReports />} />*/}
          </Route>
        </Route>
      </Routes>
    </BrowserRouter>
  );
}