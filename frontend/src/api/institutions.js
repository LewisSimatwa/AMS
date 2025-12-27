import axios from "axios";

// Temporarily mock institutions for testing frontend
export async function getInstitutions() {
  // Simulate network delay
  await new Promise((resolve) => setTimeout(resolve, 500));

  return [
    { id: 1, name: "Campus A" },
    { id: 2, name: "Campus B" },
    { id: 3, name: "Campus C" },
  ];
}

