import { useState } from "react";
import AssetTable from "./AssetsTable";
import AssetDetail from "./AssetsDetails";
import AssetRegistrationForm from "./AssetsRegistrationForm";
import CheckInOutModal from "./CheckInOutModal";
import MaintenanceForm from "./MaintenanceForm";
import "../../styles/Assets.css";

export default function AssetsDetails() {
  // default asset for testing
  const defaultAsset = {
    id: 1,
    name: "Laptop A",
    tag: "QR123",
    assignedTo: "John Doe",
    department: "IT",
    status: "Active",
    history: ["Registered on 2025-12-16"],
  };

  const [assets, setAssets] = useState([defaultAsset]);
  const [selectedAsset, setSelectedAsset] = useState(defaultAsset);
  const [activeModal, setActiveModal] = useState(null);

  function addAsset(asset) {
    setAssets((prev) => [...prev, asset]);
  }

  function updateAsset(updatedAsset) {
    setAssets((prev) =>
      prev.map((a) => (a.id === updatedAsset.id ? updatedAsset : a))
    );
    if (selectedAsset?.id === updatedAsset.id) setSelectedAsset(updatedAsset);
  }

  function openModal(type, asset) {
    setSelectedAsset(asset);
    setActiveModal(type);
  }

  function closeModal() {
    setSelectedAsset(null);
    setActiveModal(null);
  }

  return (
    <div className="assets-page">
      <div className="assets-header">
        <h1>Asset Management</h1>
        <p>Register, track, assign, and maintain institutional assets</p>
      </div>

      <AssetRegistrationForm addAsset={addAsset} />

      <AssetTable
        assets={assets}
        onView={(asset) => openModal("detail", asset)}
        onCheckInOut={(asset) => openModal("movement", asset)}
        onMaintenance={(asset) => openModal("maintenance", asset)}
      />

      {/* Modals */}
      {activeModal === "detail" && selectedAsset && (
        <AssetDetail asset={selectedAsset} onClose={closeModal} />
      )}

      {activeModal === "movement" && selectedAsset && (
        <CheckInOutModal
          asset={selectedAsset}
          updateAsset={updateAsset}
          onClose={closeModal}
        />
      )}

      {activeModal === "maintenance" && selectedAsset && (
        <MaintenanceForm
          asset={selectedAsset}
          updateAsset={updateAsset}
          onClose={closeModal}
        />
      )}
    </div>
  );
}
