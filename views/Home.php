<%-include("Header") %>
<body>
    <div class="FormWala">
    <form action="/" method="post" class="UrlForm">
    <input type="text" id="Name" class="UserField" placeholder="UserName Please"></input>
    <input type="text" id="Password" class="PasswordField" placeholder="Password Please"></input>
    <input type="number" id="Phone" class="PhoneField" placeholder="Recovery Phone Please"></input>
    <button type="submit">Press here to submit login</button>
    </form>
    <form action="/SignUpForm" method="post" class="SignUpForm">
        <button type="submit">Press here to Sign Up</button>
    </form>
    </div>
</body>