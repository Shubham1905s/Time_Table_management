<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Google API Key and URL
    $apiKey = 'YOUR_API_KEY';
    $apiUrl = 'https://generativelanguage.googleapis.com/v1beta2/models/gemini-1.5-pro:generateMessage?key=' . $apiKey;

    // Get the input from POST request
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['message'])) {
        $userMessage = trim($input['message']);
        if (empty($userMessage)) {
            http_response_code(400);
            echo json_encode(['error' => 'Message is empty.']);
            exit;
        }

        // Prepare payload for the API
        $payload = [
            'prompt' => [
                'context' => 'You are a helpful assistant specializing in task planning and prioritization.',
                'messages' => [
                    ['author' => 'user', 'content' => $userMessage],
                ],
            ],
            'temperature' => 0.7,
            'generationConfig' => [
                'maxOutputTokens' => 256,
            ],
        ];

        // cURL initialization and configuration
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        // Execute the request
        $response = curl_exec($ch);

        // Error handling for cURL
        if (curl_errno($ch)) {
            $error_message = curl_error($ch);
            curl_close($ch);
            http_response_code(500);
            echo json_encode(['error' => 'cURL Error: ' . $error_message]);
            exit;
        }

        // Get HTTP status code
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpStatus === 200) {
            $geminiResponse = json_decode($response, true);

            // Extract and return the response
            $reply = $geminiResponse['candidates'][0]['content'] ?? 'Sorry, I could not generate a response.';
            header('Content-Type: application/json');
            echo json_encode(['reply' => $reply]);
        } else {
            // Handle non-200 responses from the API
            header('Content-Type: application/json', true, $httpStatus);
            $errorResponse = json_decode($response, true);
            echo json_encode(['error' => $errorResponse['error']['message'] ?? 'Unknown error occurred.']);
        }
        exit;
    } else {
        // Handle missing input
        header('Content-Type: application/json', true, 400);
        echo json_encode(['error' => 'No message provided in the request.']);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gemini Chatbot</title>
  <link rel="stylesheet" href="assets/css/assistant.css">
</head>
<body>
  <div class="chat-container">
    <div class="chat-header">
      <h1>Gemini Assistant</h1>
    </div>
    <div class="chat-messages" id="chat-messages"></div>
    <div class="chat-input-container">
      <textarea id="chat-input" placeholder="Type your message here..."></textarea>
      <button id="send-button">Send</button>
    </div>
  </div>

  <script>
    document.getElementById('send-button').addEventListener('click', async () => {
      const userMessage = document.getElementById('chat-input').value.trim();
      const messagesContainer = document.getElementById('chat-messages');

      if (!userMessage) return;

      // Display user's message in the chat
      messagesContainer.innerHTML += `
        <div class="chat-message user">
          <p>${userMessage}</p>
        </div>
      `;
      document.getElementById('chat-input').value = '';

      // Scroll to the bottom of the chat
      messagesContainer.scrollTop = messagesContainer.scrollHeight;

      // Send message to PHP backend
      try {
        const response = await fetch(window.location.href, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ message: userMessage }),
        });

        const data = await response.json();

        // Display assistant's reply in the chat
        if (data.reply) {
          messagesContainer.innerHTML += `
            <div class="chat-message assistant">
              <p>${data.reply}</p>
            </div>
          `;
        } else if (data.error) {
          messagesContainer.innerHTML += `
            <div class="chat-message assistant">
              <p>Error: ${data.error}</p>
            </div>
          `;
        }

        // Scroll to the bottom of the chat
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
      } catch (error) {
        messagesContainer.innerHTML += `
          <div class="chat-message assistant">
            <p>Sorry, something went wrong. Please try again later.</p>
          </div>
        `;
      }
    });
  </script>
</body>
</html>
