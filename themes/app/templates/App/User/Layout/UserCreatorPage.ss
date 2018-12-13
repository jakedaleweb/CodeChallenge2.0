<% include SideBar %>
<div class="content-container unit size3of4 lastUnit">
    <article>
        <h1>$Title</h1>
        <div class="content">$Content</div>
    </article>
    <% with $GeneratedMember %>
        <img src="$ProfilePic"/>
        <p>This is $FirstName $Surname, you can email them on <a href="mailto:$Email">$Email</a> and call them on <a href="tel:$Cell">$Cell</a>.</p>
    <% end_with %>
</div>
