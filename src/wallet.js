import { loadConnectAndInitialize } from "@stripe/connect-js";

const instance = loadConnectAndInitialize({
  publishableKey:
    "{{pk_test_51Q0mWz08GrFUpp2bxZpZ55e16ClgZ5jBudZW6buIyuzozAvD3OpRNb2eRHBcZJjpEtUvPjEeW3QsQj4QFlnZE58H00hT5LUq36}}",
  fetchClientSecret: fetchClientSecret,
});

// Function to fetch the Stripe account ID from the backend
const fetchStripeAccountId = async () => {
  const response = await fetch("/api/get_stripe_account_id.php", {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
    },
  });

  if (response.ok) {
    const { stripe_account_id } = await response.json();
    return stripe_account_id;
  } else {
    const { error } = await response.json();
    console.error("Error fetching Stripe account ID:", error);
    return null;
  }
};

// Function to fetch the client secret
const fetchClientSecret = async (connectedAccountId) => {
  const response = await fetch("/api/account_session.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      account: connectedAccountId, // The doctor's connected account ID
    }),
  });

  if (!response.ok) {
    const { error } = await response.json();
    console.error("Error fetching client secret:", error);
    return undefined;
  } else {
    const { client_secret } = await response.json();
    return client_secret;
  }
};

// Function to load Stripe onboarding flow
const loadStripeOnboarding = (client_secret) => {
  const stripeInstance = loadConnectAndInitialize({
    publishableKey:
      "pk_test_51Q0mWz08GrFUpp2bxZpZ55e16ClgZ5jBudZW6buIyuzozAvD3OpRNb2eRHBcZJjpEtUvPjEeW3QsQj4QFlnZE58H00hT5LUq36", // Replace with your real publishable key
    fetchClientSecret: () => Promise.resolve(client_secret),
    appearance: {
      overlays: "dialog",
      variables: {
        colorPrimary: "#dbdfe8",
      },
    },
  });

  // Get the container where the onboarding component will be embedded
  const onboardingContainer = document.getElementById(
    "embedded-onboarding-container"
  );
  const onboardingComponent = stripeInstance.create("account-onboarding");
  onboardingComponent.setOnExit(() => {
    console.log("User exited the onboarding flow");
    // Optionally, handle redirection or updates after onboarding
  });
  onboardingContainer.appendChild(onboardingComponent);
};

// Event listener for the wallet button click
document.getElementById("walletButton").addEventListener("click", async () => {
  try {
    // Fetch the connected account ID from the backend
    const connectedAccountId = await fetchStripeAccountId();

    if (!connectedAccountId) {
      console.error("No Stripe account ID found for the user.");
      return;
    }

    // Call the API to create an AccountSession
    const client_secret = await fetchClientSecret(connectedAccountId);

    if (client_secret) {
      // Load the Stripe onboarding if client_secret is retrieved
      loadStripeOnboarding(client_secret);
    } else {
      console.error("Failed to fetch client secret for onboarding");
    }
  } catch (error) {
    console.error("Error during onboarding:", error);
  }
});
