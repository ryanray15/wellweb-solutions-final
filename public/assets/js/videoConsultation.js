import ZoomVideo from "@zoom/videosdk";

const client = ZoomVideo.createClient();

client.init("en-US", "CDN"); // Initialize with language and resources

function joinSession(sessionName, sessionPassword, userName, signature) {
  client
    .join(sessionName, sessionPassword, userName, signature)
    .then(() => {
      console.log("Joined the session successfully");
    })
    .catch((error) => {
      console.error(error);
    });
}
