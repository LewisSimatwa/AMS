import { BrowserRouter, Routes, Route } from "react-router-dom";
import Login from "./features/auth/Login";
import CreateAccount from "./features/auth/CreateAccount";
import ForgotPassword from "./features/auth/ForgotPassword";
import SelectInstitution from "./features/institutions/SelectInstitution";
import Dashboard from "./features/dashboard/Dashboard";
import AssetsDetails from "./features/assets/AssetsDetails";
import PredictiveAnalytics from "./features/analytics/PredictiveAnalytics";
import Reports from "./features/reports/ReportsPage";
import UserManagement from "./features/users/UserManagement";
import MainLayout from "./layouts/MainLayout";
import Maintenance from "./features/maintenance/Maintenance";

export default function AppRouter() {
  return (
    <BrowserRouter>
      <Routes>
        {/* Public routes */}
        <Route path="/" element={<Login />} />
        <Route path="/login" element={<Login />} />
        <Route path="/create-account" element={<CreateAccount />} />
        <Route path="/forgot-password" element={<ForgotPassword />} />
        <Route path="/select-institution" element={<SelectInstitution />} />

        {/* Main layout routes */}
        <Route element={<MainLayout />}>
          <Route path="/dashboard" element={<Dashboard />} />
          <Route path="/assets" element={<AssetsDetails />} />
          <Route path="/reports" element={<Reports />} />
          <Route path="/maintanance" element={<Maintenance />} />
          <Route path="/analytics" element={<PredictiveAnalytics />} />
          <Route path="/users" element={<UserManagement />} />
        </Route>

                {/*
        // Protected routes (to enable later)
        <Route element={<ProtectedRoute />}>
          <Route element={<MainLayout />}>
            <Route path="/dashboard" element={<Dashboard />} />
            <Route path="/assets" element={<AssetsDetails />} />
            <Route path="/analytics" element={<PredictiveAnalytics />} />
            <Route path="/reports" element={<Reports />} />
            <Route path="/users" element={<UserManagement />} />
          </Route>
        </Route>
        */}


      </Routes>
    </BrowserRouter>
  );
}

