import { useState, useEffect } from "react";
import { 
  Package, 
  Search, 
  Filter, 
  Eye, 
  AlertCircle,
  Building2,
  Calendar,
  DollarSign,
  X,
  Archive
} from "lucide-react";
import '../../styles/SuperAdmin/Assets.css'

export default function GlobalAssetOversight() {
  const [assets, setAssets] = useState([]);
  const [institutions, setInstitutions] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState("");
  const [filterInstitution, setFilterInstitution] = useState("");
  const [filterStatus, setFilterStatus] = useState("");
  const [selectedAsset, setSelectedAsset] = useState(null);
  const [showDetailModal, setShowDetailModal] = useState(false);
  const [showRetireModal, setShowRetireModal] = useState(false);
  const [retireReason, setRetireReason] = useState("");

  useEffect(() => {
    fetchAssets();
    fetchInstitutions();
  }, []);

  const fetchAssets = async () => {
    try {
      const token = localStorage.getItem("token");
      const response = await fetch("http://localhost:8000/api/super_admin/assets", {
        headers: {
          "Authorization": `Bearer ${token}`
        }
      });
      const data = await response.json();
      setAssets(data.assets || []);
    } catch (error) {
      console.error("Failed to fetch assets:", error);
    } finally {
      setLoading(false);
    }
  };

  const fetchInstitutions = async () => {
    try {
      const token = localStorage.getItem("token");
      const response = await fetch("http://localhost:8000/api/super_admin/institutions", {
        headers: {
          "Authorization": `Bearer ${token}`
        }
      });
      const data = await response.json();
      setInstitutions(data.institutions || []);
    } catch (error) {
      console.error("Failed to fetch institutions:", error);
    }
  };

  const handleViewDetails = async (asset) => {
    try {
      const token = localStorage.getItem("token");
      const response = await fetch(
        `http://localhost:8000/api/super_admin/asset-history?asset_id=${asset.id}`,
        {
          headers: {
            "Authorization": `Bearer ${token}`
          }
        }
      );
      const data = await response.json();
      setSelectedAsset({
        ...asset,
        history: data.history || []
      });
      setShowDetailModal(true);
    } catch (error) {
      console.error("Failed to fetch asset history:", error);
      setSelectedAsset({
        ...asset,
        history: []
      });
      setShowDetailModal(true);
    }
  };

  const handleForceRetire = (asset) => {
    setSelectedAsset(asset);
    setRetireReason("");
    setShowRetireModal(true);
  };

  const handleSubmitRetire = async (e) => {
    e.preventDefault();
    try {
      const token = localStorage.getItem("token");
      const response = await fetch("http://localhost:8000/api/super_admin/force-retire", {
        method: "POST",
        headers: {
          "Authorization": `Bearer ${token}`,
          "Content-Type": "application/json"
        },
        body: JSON.stringify({
          asset_id: selectedAsset.id,
          reason: retireReason
        })
      });

      if (response.ok) {
        setShowRetireModal(false);
        fetchAssets();
        alert("Asset retired successfully");
      } else {
        const errorData = await response.json();
        alert(`Failed to retire asset: ${errorData.error || 'Unknown error'}`);
      }
    } catch (error) {
      console.error("Failed to retire asset:", error);
      alert("Failed to retire asset");
    }
  };

  const filteredAssets = assets.filter(asset => {
    const matchesSearch = asset.name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         asset.serial_number?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         asset.asset_code?.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesInstitution = !filterInstitution || asset.institution_id == filterInstitution;
    const matchesStatus = !filterStatus || asset.status === filterStatus;
    return matchesSearch && matchesInstitution && matchesStatus;
  });

  const getStatusColor = (status) => {
    switch(status?.toLowerCase()) {
      case 'available': return 'bg-green-100 text-green-700';
      case 'on_loan': return 'bg-blue-100 text-blue-700';
      case 'maintenance': return 'bg-yellow-100 text-yellow-700';
      case 'retired': return 'bg-red-100 text-red-700';
      default: return 'bg-gray-100 text-gray-700';
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-full">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="p-8">
      {/* Header */}
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900">Global Asset Oversight</h1>
        <p className="text-gray-600 mt-1">View and monitor all assets across institutions</p>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-lg shadow p-6 mb-6">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" size={20} />
            <input
              type="text"
              placeholder="Search assets..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div className="relative">
            <Building2 className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" size={20} />
            <select
              value={filterInstitution}
              onChange={(e) => setFilterInstitution(e.target.value)}
              className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 appearance-none"
            >
              <option value="">All Institutions</option>
              {institutions.map((inst) => (
                <option key={inst.id} value={inst.id}>{inst.name}</option>
              ))}
            </select>
          </div>
          <div className="relative">
            <Filter className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" size={20} />
            <select
              value={filterStatus}
              onChange={(e) => setFilterStatus(e.target.value)}
              className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 appearance-none"
            >
              <option value="">All Status</option>
              <option value="available">Available</option>
              <option value="in_use">In Use</option>
              <option value="maintenance">Maintenance</option>
              <option value="retired">Retired</option>
            </select>
          </div>
        </div>
      </div>

      {/* Stats Summary */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div className="bg-white rounded-lg shadow p-4">
          <p className="text-sm text-gray-600">Total Assets</p>
          <p className="text-2xl font-bold text-gray-900">{filteredAssets.length}</p>
        </div>
        <div className="bg-white rounded-lg shadow p-4">
          <p className="text-sm text-gray-600">Available</p>
          <p className="text-2xl font-bold text-green-600">
            {filteredAssets.filter(a => a.status === 'available').length}
          </p>
        </div>
        <div className="bg-white rounded-lg shadow p-4">
          <p className="text-sm text-gray-600">In Use</p>
          <p className="text-2xl font-bold text-blue-600">
            {filteredAssets.filter(a => a.status === 'in_use').length}
          </p>
        </div>
        <div className="bg-white rounded-lg shadow p-4">
          <p className="text-sm text-gray-600">Retired</p>
          <p className="text-2xl font-bold text-red-600">
            {filteredAssets.filter(a => a.status === 'retired').length}
          </p>
        </div>
      </div>

      {/* Assets Table */}
      <div className="bg-white rounded-lg shadow overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-gray-50 border-b border-gray-200">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asset</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Institution</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200">
              {filteredAssets.length > 0 ? (
                filteredAssets.map((asset) => (
                  <tr key={asset.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4">
                      <div className="flex items-center space-x-3">
                        <div className="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                          <Package className="text-blue-600" size={20} />
                        </div>
                        <div>
                          <p className="font-medium text-gray-900">{asset.name}</p>
                          <p className="text-sm text-gray-500">{asset.serial_number || 'N/A'}</p>
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <p className="text-sm text-gray-900">{asset.institution_name}</p>
                    </td>
                    <td className="px-6 py-4">
                      <p className="text-sm text-gray-900">{asset.category_name}</p>
                    </td>
                    <td className="px-6 py-4">
                      <span className={`px-2 py-1 text-xs rounded-full ${getStatusColor(asset.status)}`}>
                        {asset.status}
                      </span>
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex items-center text-sm text-gray-900">
                        <DollarSign size={14} className="mr-1" />
                        {parseFloat(asset.purchase_price || 0).toLocaleString()}
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex space-x-2">
                        <button
                          onClick={() => handleViewDetails(asset)}
                          className="p-2 text-blue-600 hover:bg-blue-50 rounded transition-colors"
                          title="View Details"
                        >
                          <Eye size={18} />
                        </button>
                        {asset.status !== 'retired' && (
                          <button
                            onClick={() => handleForceRetire(asset)}
                            className="p-2 text-red-600 hover:bg-red-50 rounded transition-colors"
                            title="Force Retire"
                          >
                            <Archive size={18} />
                          </button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan="6" className="px-6 py-8 text-center text-gray-500">
                    No assets found
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Asset Detail Modal */}
      {showDetailModal && selectedAsset && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
            <div className="p-6 border-b border-gray-200 flex items-center justify-between">
              <h2 className="text-xl font-bold text-gray-900">Asset Details</h2>
              <button onClick={() => setShowDetailModal(false)} className="text-gray-500 hover:text-gray-700">
                <X size={24} />
              </button>
            </div>
            <div className="p-6">
              <div className="grid grid-cols-2 gap-4 mb-6">
                <div>
                  <p className="text-sm text-gray-600">Asset Name</p>
                  <p className="font-medium text-gray-900">{selectedAsset.name}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-600">Serial Number</p>
                  <p className="font-medium text-gray-900">{selectedAsset.serial_number || 'N/A'}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-600">Institution</p>
                  <p className="font-medium text-gray-900">{selectedAsset.institution_name}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-600">Category</p>
                  <p className="font-medium text-gray-900">{selectedAsset.category_name}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-600">Status</p>
                  <span className={`px-2 py-1 text-xs rounded-full ${getStatusColor(selectedAsset.status)}`}>
                    {selectedAsset.status}
                  </span>
                </div>
                <div>
                  <p className="text-sm text-gray-600">Purchase Price</p>
                  <p className="font-medium text-gray-900">${parseFloat(selectedAsset.purchase_price || 0).toLocaleString()}</p>
                </div>
              </div>

              <div className="border-t border-gray-200 pt-6">
                <h3 className="font-semibold text-gray-900 mb-4">Asset History</h3>
                <div className="space-y-3">
                  {selectedAsset.history && selectedAsset.history.length > 0 ? (
                    selectedAsset.history.map((entry, index) => (
                      <div key={index} className="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg">
                        <Calendar className="text-gray-400 mt-1" size={16} />
                        <div className="flex-1">
                          <p className="text-sm font-medium text-gray-900">{entry.action}</p>
                          <p className="text-xs text-gray-600 mt-1">{entry.description}</p>
                          <p className="text-xs text-gray-500 mt-1">
                            {new Date(entry.timestamp).toLocaleString()} by {entry.user_name}
                          </p>
                        </div>
                      </div>
                    ))
                  ) : (
                    <p className="text-gray-500 text-sm">No history available</p>
                  )}
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Force Retire Modal */}
      {showRetireModal && selectedAsset && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div className="p-6 border-b border-gray-200 flex items-center justify-between">
              <div className="flex items-center space-x-2">
                <AlertCircle className="text-red-600" size={24} />
                <h2 className="text-xl font-bold text-gray-900">Force Retire Asset</h2>
              </div>
              <button onClick={() => setShowRetireModal(false)} className="text-gray-500 hover:text-gray-700">
                <X size={24} />
              </button>
            </div>
            <form onSubmit={handleSubmitRetire} className="p-6">
              <div className="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <p className="text-sm text-red-800">
                  <strong>Warning:</strong> This action will retire the asset "{selectedAsset.name}". This is a high-risk operation and will be logged.
                </p>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Reason for Retirement</label>
                <textarea
                  value={retireReason}
                  onChange={(e) => setRetireReason(e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500"
                  rows="4"
                  required
                  placeholder="Provide a detailed reason for retiring this asset..."
                />
              </div>
              <div className="flex justify-end space-x-3 mt-6">
                <button
                  type="button"
                  onClick={() => setShowRetireModal(false)}
                  className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="flex items-center space-x-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
                >
                  <Archive size={18} />
                  <span>Retire Asset</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}