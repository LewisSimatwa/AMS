import { BrowserRouter, Routes, Route } from "react-router-dom";
import MainLayout from "./layouts/MainLayout";
import Dashboard from "./features/dashboard/Dashboard";
// import other pages when ready

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        {/* All routes using MainLayout */}
        <Route element={<MainLayout />}>
          {/* Default dashboard route */}
          <Route index element={<Dashboard />} />       {/* renders at "/" */}
          <Route path="dashboard" element={<Dashboard />} />  {/* renders at "/dashboard" */}
          {/* Add more routes here like /assets, /analytics etc */}
        </Route>
      </Routes>
    </BrowserRouter>
  );
}
