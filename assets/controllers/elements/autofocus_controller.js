import {Controller} from "@hotwired/stimulus";

export default class extends Controller
{
    connect()
    {
        this.element.focus({preventScroll: false});
        this.element.scrollIntoView({block: "center", behavior: "smooth"});
    }
}
