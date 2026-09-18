import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import Home from './pages/Home';
import Dashboard from './pages/Dashboard';
import Login from './pages/Login';
import RoleSelection from './pages/RoleSelection';
import Register from './pages/Register';
import RegistrationStatus from './pages/RegistrationStatus';
import RegistrationDetail from './pages/Admin/RegistrationDetail';
import Registrations from './pages/Admin/Registrations';
import StaffOverview from './pages/Staff/Overview';
import ProfileOverview from './pages/Profile/Overview';
import ProfileEdit from './pages/Profile/Edit';
import ProfileSection from './pages/Profile/Section';
import CvIndex from './pages/Cv/Index';
import CvEditor from './pages/Cv/Editor';
import CvPreview from './pages/Cv/Preview';
import PublicCv from './pages/Cv/Public';
import CvTemplateIndex from './pages/Admin/CvTemplates/Index';
import CvTemplateEdit from './pages/Admin/CvTemplates/Edit';
import CvTemplatePreview from './pages/Admin/CvTemplates/Preview';
import EventIndex from './pages/Event/Index';
import EventShow from './pages/Event/Show';
import MyEvents from './pages/Event/MyEvents';
import AdminEventIndex from './pages/Admin/Events/Index';
import AdminEventEdit from './pages/Admin/Events/Edit';
import AdminEventParticipants from './pages/Admin/Events/Participants';
import CompanyRegister from './pages/Company/Register';
import CompanyLogin from './pages/Company/Login';
import CompanyDashboard from './pages/Company/Dashboard';
import CompanyRecruiters from './pages/Company/Recruiters';
import AdminCompanies from './pages/Admin/Companies/Index';
import TalentSearch from './pages/Talent/Search';
import TalentProfile from './pages/Talent/Profile';
import JobIndex from './pages/Jobs/Index';
import JobShow from './pages/Jobs/Show';
import JobApplications from './pages/Jobs/Applications';
import JobInvitations from './pages/Jobs/Invitations';
import CompanyJobIndex from './pages/Company/Jobs/Index';
import CompanyJobEdit from './pages/Company/Jobs/Edit';
import CompanyJobApplicants from './pages/Company/Jobs/Applicants';
import AdminJobIndex from './pages/Admin/Jobs/Index';
import AdminJobEdit from './pages/Admin/Jobs/Edit';
import NotificationsIndex from './pages/Notifications/Index';
import TracerIndex from './pages/Tracer/Index';
import TracerShow from './pages/Tracer/Show';
import AdminTracerIndex from './pages/Admin/Tracer/Index';
import AdminJobImport from './pages/Admin/Jobs/Import';

const pages = {
    Home,
    Dashboard,
    Login,
    RoleSelection,
    Register,
    RegistrationStatus,
    'Admin/RegistrationDetail': RegistrationDetail,
    'Admin/Registrations': Registrations,
    'Staff/Overview': StaffOverview,
    'Profile/Overview': ProfileOverview,
    'Profile/Edit': ProfileEdit,
    'Profile/Section': ProfileSection,
    'Cv/Index': CvIndex,
    'Cv/Editor': CvEditor,
    'Cv/Preview': CvPreview,
    'Cv/Public': PublicCv,
    'Admin/CvTemplates/Index': CvTemplateIndex,
    'Admin/CvTemplates/Edit': CvTemplateEdit,
    'Admin/CvTemplates/Preview': CvTemplatePreview,
    'Event/Index': EventIndex,
    'Event/Show': EventShow,
    'Event/MyEvents': MyEvents,
    'Admin/Events/Index': AdminEventIndex,
    'Admin/Events/Edit': AdminEventEdit,
    'Admin/Events/Participants': AdminEventParticipants,
    'Company/Register': CompanyRegister,
    'Company/Login': CompanyLogin,
    'Company/Dashboard': CompanyDashboard,
    'Company/Recruiters': CompanyRecruiters,
    'Admin/Companies/Index': AdminCompanies,
    'Talent/Search': TalentSearch,
    'Talent/Profile': TalentProfile,
    'Jobs/Index': JobIndex,
    'Jobs/Show': JobShow,
    'Jobs/Applications': JobApplications,
    'Jobs/Invitations': JobInvitations,
    'Company/Jobs/Index': CompanyJobIndex,
    'Company/Jobs/Edit': CompanyJobEdit,
    'Company/Jobs/Applicants': CompanyJobApplicants,
    'Admin/Jobs/Index': AdminJobIndex,
    'Admin/Jobs/Edit': AdminJobEdit,
    'Notifications/Index': NotificationsIndex,
    'Tracer/Index': TracerIndex,
    'Tracer/Show': TracerShow,
    'Admin/Tracer/Index': AdminTracerIndex,
    'Admin/Jobs/Import': AdminJobImport,
};

createInertiaApp({
    resolve: (name) => {
        if (!(name in pages)) {
            throw new Error(`Unknown page: ${name}`);
        }

        return pages[name as keyof typeof pages];
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
    title: (title) => title ? `${title} · SAFA KARIR` : 'SAFA KARIR — Farmasi UBP',
});
