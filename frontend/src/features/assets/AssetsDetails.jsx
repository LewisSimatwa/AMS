import { useState } from "react";
import AssetTable from "./AssetsTable";
import AssetDetail from "./AssetsDetails";
import AssetRegistrationForm from "./AssetsRegistrationForm";
import DeleteConfirmationModal from "./DeleteConfirmationModal";
import "../../styles/Assets.css";

export default function AssetsDetails() {
  const [refreshTrigger, setRefreshTrigger] = useState(0);
  const [selectedAsset, setSelectedAsset] = useState(null);
  const [activeModal, setActiveModal] = useState(null);

  function openModal(type, asset) {
    setSelectedAsset(asset);
    setActiveModal(type);
  }

  function closeModal() {
    setSelectedAsset(null);
    setActiveModal(null);
  }

  function handleDeleteComplete() {
    // Trigger refresh after deletion
    setRefreshTrigger((prev) => prev + 1);
    closeModal();
  }

  function handleAssetRegistered() {
    // Trigger refresh after new asset is registered
    setRefreshTrigger((prev) => prev + 1);
  }

  return (
    <div className="assets-page">
      <div className="assets-header">
        <h1>Asset Management</h1>
        <p>Register, track, assign, and maintain institutional assets</p>
      </div>

      <AssetRegistrationForm onAssetRegistered={handleAssetRegistered} />

      <AssetTable
        onView={(asset) => openModal("detail", asset)}
        onDelete={(asset) => openModal("delete", asset)}
        refreshTrigger={refreshTrigger}
      />

      {/* Modals */}
      {activeModal === "detail" && selectedAsset && (
        <AssetDetail asset={selectedAsset} onClose={closeModal} />
      )}

      {activeModal === "delete" && selectedAsset && (
        <DeleteConfirmationModal
          asset={selectedAsset}
          onClose={closeModal}
          onConfirm={handleDeleteComplete}
        />
      )}
    </div>
  );
}