<!DOCTYPE html>
<html>
<head>
    <title>Propeller CRM Client</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input, textarea { width: 100%; padding: 8px; margin-bottom: 10px; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
        button:hover { background: #0056b3; }
        .response { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <div id="app">
        <h1>Propeller CRM Client</h1>
        <h2>Add Subscriber</h2>
        <div class="form-group">
            <label>Email:</label>
            <input v-model="email" placeholder="Enter email" required />
        </div>
        <div class="form-group">
            <label>First Name:</label>
            <input v-model="firstName" placeholder="Enter first name" />
        </div>
        <div class="form-group">
            <label>Last Name:</label>
            <input v-model="lastName" placeholder="Enter last name" />
        </div>
        <div class="form-group">
            <label>Date of Birth (YYYY-MM-DD):</label>
            <input v-model="dob" placeholder="Enter date of birth" />
        </div>
        <div class="form-group">
            <label><input type="checkbox" v-model="marketingConsent" /> Marketing Consent</label>
        </div>
        <div class="form-group">
            <label>List IDs (comma-separated):</label>
            <input v-model="listIds" placeholder="Enter list IDs (e.g., id1,id2)" />
        </div>
        <button @click="addSubscriber">Add Subscriber</button>


        <h2>Send Enquiry</h2>
        <div class="form-group">
            <label>Subscriber ID:</label>
            <input v-model="subscriberId" placeholder="Enter subscriber ID" required />
        </div>
        <div class="form-group">
            <label>Message:</label>
            <textarea v-model="message" placeholder="Enter enquiry message" required></textarea>
        </div>
        <button @click="sendEnquiry">Send Enquiry</button>

        <p class="response" v-if="responseMessage" v-text="responseMessage"></p>
        <p class="error" v-if="errorMessage" v-text="errorMessage"></p>
    </div>
    @vite('resources/js/app.js')
</body>
</html>
