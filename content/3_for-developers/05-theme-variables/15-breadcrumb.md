# Breadcrumb

The `{{ breadcrumb }}` variable contains the breadcrumb for the page as an one dimensional array. The array contains item objects. You can loop over the breadcrumb and print the elements out like this: 

    <ul class="breadcrumb">
    {% for element in breadcrumb %}
        <li><a href="{{ element.urlRel }}">{{ element.name }}</a></li>
    {% endfor %}
    </ul>

All informations of the items are available, so check the chapter about the item variable for more details.